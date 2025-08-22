<?php
/**
 *  Opencast\Models\REST\RestClient.php - The REST Client for Opencast
 */
namespace Opencast\Models\REST;

use Config as StudipConfig;
use Context;
use GuzzleHttp\HandlerStack;
use OpencastApi\Opencast;
use OpencastApi\Rest\OcRestClient;
use Opencast\Errors\RESTError;
use Opencast\Middlewares\REST\ConnectionMiddlewares;
use Opencast\Models\Config;

class RestClient
{
    static $me = [];

    protected $base_url,
        $username,
        $password,
        $oc_version,
        $config_id,
        $advance_search = false;

    protected $timeout_seconds, $connect_timeout_seconds = 0;

    public $opencastApi;
    public $ocRestClient;

    public $serviceName = 'ParentRestClientClass';

    /**
     * Get singleton instance of client
     *
     * @param $config_id config id
     * @return static
     * @throws RESTError
     */
    static function getInstance($config_id = null)
    {
        // use default config if nothing else is given
        if (is_null($config_id) || $config_id === false) {
            $config_id = Config::getConfigIdForCourse(Context::getId()) ?
                : StudipConfig::get()->OPENCAST_DEFAULT_SERVER;
        }

        if (!property_exists(get_called_class(), 'me')) {
            throw new RESTError('Every child of ' . get_class() . ' needs to implement static property "$me"');
        }

        if (@!is_object(static::$me[$config_id])) {
            static::$me[$config_id] = new static($config_id);
        }

        return static::$me[$config_id];
    }

    public function __construct($config)
    {
        $this->config_id  = $config['config_id'];
        $this->base_url   = $config['service_url'];
        $this->username   = $config['service_user'];
        $this->password   = $config['service_password'];
        $this->oc_version = $config['service_version'];
        if (!empty($config['timeout_ms'])) {
            $this->timeout_seconds = (float) ((int) $config['timeout_ms'] / 1000);
        }
        if (!empty($config['connect_timeout_ms'])) {
            $this->connect_timeout_seconds = (float) ((int) $config['connect_timeout_ms'] / 1000);
        }

        $oc_config = [
            'url' => $config['service_url'],
            'username' => $config['service_user'],
            'password' => $config['service_password'],
            'timeout' => $this->timeout_seconds,
            'connect_timeout' => $this->connect_timeout_seconds,
            'features' => [
                'lucene' => false
            ],
            'guzzle' => [
                'verify' => (
                    isset($config['settings']['ssl_ignore_cert_errors'])
                    && $config['settings']['ssl_ignore_cert_errors'] === true)
                    ? false : true
            ]
        ];

        $handlers = [];

        // Add retry middleware only if a timeout is set (greater than 0).
        // This prevents unnecessary retries when no timeout is configured and helps handle transient connection issues.
        if ($this->timeout_seconds > 0) {
            $handlers[] = ConnectionMiddlewares::failedRequestsRetry(3);
        }

        // Only add handlers when there is something!
        if (!empty($handlers)) {
            $stack = HandlerStack::create();
            foreach ($handlers as $handler) {
                $stack->push($handler);
            }
            $oc_config['handler'] = $stack;
        }

        $this->opencastApi = new Opencast($oc_config);
        $this->ocRestClient = new OcRestClient($oc_config);
    }

    /**
     * Get the Guzzle client options suitable for stream downloading files.
     *
     * @return array
     */
    public function getStreamDownloadConfig() {
        return [
            'auth'            => [$this->username, $this->password],
            'timeout'         => 0, // Unlimited, to ensure download goes well!
            'connect_timeout' => $this->connect_timeout_seconds,
            'stream' => true,
        ];
    }

    public function fileRequest($file_url)
    {
        $response = $this->ocRestClient->get($file_url, [
            'auth'            => [$this->username, $this->password],
            'timeout'         => $this->timeout_seconds,
            'connect_timeout' => $this->connect_timeout_seconds,
        ]);

        $result = [];
        $result['code']   = $response->getStatusCode();
        $result['reason'] = $response->getReasonPhrase();
        $body = '';
        if ($result['code'] < 400 && !empty((string) $response->getBody())) {
            $body = $response->getBody();
        }

        $result['body']     = $body;
        $result['mimetype'] = $response->getHeader('Content-Type');

        return $result;
    }
}
