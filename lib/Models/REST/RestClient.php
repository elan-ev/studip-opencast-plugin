<?php
/**
 *  Opencast\Models\REST\RestClient.php - The REST Client for Opencast
 */
namespace Opencast\Models\REST;

use Opencast\Models\Config;
use Opencast\Errors\RESTError;
use \Context;
use OpencastApi\Opencast;
use OpencastApi\Rest\OcRestClient;

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
            $config_id = Config::getConfigIdForCourse(Context::getId()) ?: 1;
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
            'timeout' => 5,
            'connect_timeout' => 5,
            'features' => [
                'lucene' => false
            ]
        ];
        $this->opencastApi = new Opencast($oc_config);
        $this->ocRestClient = new OcRestClient($oc_config);
    }
}
