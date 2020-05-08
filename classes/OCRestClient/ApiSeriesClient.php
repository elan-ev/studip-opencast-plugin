<?php

use Opencast\Models\OCConfig;

class ApiSeriesClient extends OCRestClient
{
    static $me;
    public $serviceName = "ApiSeries";

    function __construct($config_id = 1)
    {
        if ($config = OCConfig::getConfigForService('apiseries', $config_id)) {
            parent::__construct($config);
        } else {
            throw new Exception (_("Die Konfiguration wurde nicht korrekt angegeben"));
        }
    }
}
