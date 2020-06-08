<?php

namespace Opencast\Models\REST;

use Opencast\Models\OCConfig;

class ServicesClient extends RestClient
{
    static $me;

    function __construct($config_id = 1)
    {
        $this->serviceName = 'ServicesClient';

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
