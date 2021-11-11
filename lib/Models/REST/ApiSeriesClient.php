<?php

namespace Opencast\Models\REST;

use Opencast\Models\Config;

class ApiSeriesClient extends RestClient
{
    public static $me;
    public        $serviceName = "ApiSeries";

    function __construct($config_id = 1)
    {
        parent::__construct($config_id, 'apiseries');
    }

    public function getACL($series_id)
    {
        return json_decode(json_encode($this->getJSON('/'.$series_id. '/acl')), true);
    }
}
