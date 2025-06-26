<?php
/**
 *  Opencast\Models\REST\RestClient.php - The REST Client for Opencast
 */
namespace Opencast\Models\REST;

use Opencast\Models\Config;
use Opencast\Errors\MaintenanceError;
use Opencast\Errors\RESTError;
use OpencastApi\Opencast;
use OpencastApi\Rest\OcRestClient;

use \Context;
use \Config as StudipConfig;

class RestClient
{
    static $me = [];

    protected $base_url,
        $username,
        $password,
        $oc_version,
        $config_id,
        $advance_search = false;

    public $opencastApi;
    public $ocRestClient;

    public $serviceName = 'ParentRestClientClass';
    public $serviceType;

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
        $oc_config = [
            'url' => $config['service_url'],
            'username' => $config['service_user'],
            'password' => $config['service_password'],
            'timeout' => 60,
            'connect_timeout' => 60,
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
            'timeout'         => 0,
            'connect_timeout' => 0,
            'stream' => true,
        ];
    }

    public function fileRequest($file_url)
    {
        $response = $this->ocRestClient->get($file_url, [
            'auth'            => [$this->username, $this->password],
            'timeout'         => 2,
            'connect_timeout' => 2,
        ]);

        $result = [];
        $result['code']   = $response->getStatusCode();
        $result['reason'] = $response->getReasonPhrase();
        $body = '';
        if ($result['code'] < 400 && !empty((string) $response->getBody())) {
            $body = $response->getBody();
        }

        $result['body']     = $body;
        $result['mimetype'] = $response->getHeaderLine('Content-Type');

        return $result;
    }

    /**
     * Retrieves the configuration for the client based on service type and config ID.
     *
     * Throws a MaintenanceError if the config is null, or a generic Exception if the config is invalid.
     *
     * @param int $config_id The configuration ID.
     * @return array The configuration array.
     * @throws MaintenanceError|\Exception
     */
    protected function getConfigForClient($config_id)
    {
        $config = Config::getConfigForService($this->serviceType, $config_id);
        if (is_null($config)) {
            throw new MaintenanceError();
        } else if ($config === false) {
            throw new \Exception ($this->serviceName . ': '
                . _('Die Opencast-Konfiguration wurde nicht korrekt angegeben'));
        }
        return $config;
    }

    /**
     * Creates a read-only RestClient instance for the given config ID.
     *
     * Uses the 'search' service type to fetch configuration and returns a new instance,
     * or null if configuration is missing.
     *
     * @param int $config_id The configuration ID.
     * @return static|null The read-only RestClient instance or null if not available.
     */
    public static function createReadOnlyInstance($config_id)
    {
        $read_only_config = Config::getConfigForService('search', $config_id);
        return !empty($read_only_config) ? new static($read_only_config) : null;
    }
}
