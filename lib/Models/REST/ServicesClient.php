<?php

namespace Opencast\Models\REST;

use Opencast\Models\Config;

class ServicesClient extends RestClient
{
    public static $me;
    public        $serviceName = 'ServicesClient';

    function __construct($config_id = 1)
    {
        parent::__construct($config_id, 'services');
    }

    /**
     * getComponents() - retrieves episode system components from conntected Opencast-Matterhorn Core
     *
     *  @return array response of components
     */
    function getRESTComponents()
    {
        $service_url = "/services.json";

        if ($result = $this->getJSON($service_url)) {
            return $result->services->service;
        } else {
            return false;
        }
    }
}
