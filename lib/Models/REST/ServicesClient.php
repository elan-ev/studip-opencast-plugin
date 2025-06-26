<?php

namespace Opencast\Models\REST;

class ServicesClient extends RestClient
{
    public static $me;

    public function __construct($config_id = 1, $custom_config = null)
    {
        $this->serviceName = 'ServicesClient';
        $this->serviceType = 'services';
        $config = $custom_config ?? $this->getConfigForClient($config_id);
        parent::__construct($config);
    }

    /**
     * Retrieves episode system components from conntected Opencast Core
     *
     * @return array|boolean response of components, or false if unable to get
     */
    function getRESTComponents()
    {
        $response = $this->opencastApi->services->getServiceJSON();

        if ($response['code'] == 200) {
            if ($services = $response['body']->services->service) {
                return is_array($services) ? $services : [$services];
            }
        }

        if ($response['code'] == 0) {
            throw new \Exception($response['reason']);
        }

        return false;
    }
}
