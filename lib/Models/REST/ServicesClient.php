<?php

namespace Opencast\Models\REST;

use Opencast\Models\Config;

class ServicesClient extends RestClient
{
    public static $me;

    public function __construct($config_id = 1)
    {
        $this->serviceName = 'ServicesClient';

        if ($config = Config::getConfigForService('services', $config_id)) {
            parent::__construct($config);
        } else {
            throw new \Exception ($this->serviceName . ': '
                . _('Die Opencast-Konfiguration wurde nicht korrekt angegeben'));
        }
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
