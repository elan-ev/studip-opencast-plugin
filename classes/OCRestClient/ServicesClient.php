<?php

use Opencast\Models\OCConfig;

class ServicesClient extends OCRestClient
{
    public static $me;

    public function __construct($config_id = 1)
    {
        $this->serviceName = 'ServicesClient';

        if ($config = OCConfig::getConfigForService('services', $config_id)) {
            parent::__construct($config);
        } else {
            throw new Exception (_('Die Konfiguration wurde nicht korrekt angegeben'));
        }
    }

    /**
     * getComponents() - retrieves episode system components from conntected Opencast-Matterhorn Core
     *
     *  @return array response of components
     */
    public function getRESTComponents()
    {
        $service_url = "/services.json";
        if ($result = $this->getJSON($service_url)) {
            return $result->services->service;
        } else {
            return false;
        }
    }
}
