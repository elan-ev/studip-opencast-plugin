<?php

use Opencast\Models\OCConfig;

class ApiSeriesClient extends OCRestClient
{
    public static $me;
    public $serviceName = "ApiSeries";

    function __construct($config_id = 1)
    {
        if ($config = OCConfig::getConfigForService('apiseries', $config_id)) {
            parent::__construct($config);
        } else {
            throw new Exception (_("Die Konfiguration wurde nicht korrekt angegeben"));
        }
    }

    public function getACL($series_id)
    {
        return json_decode(json_encode($this->getJSON('/'.$series_id. '/acl')), true);
    }

    public function setACL($series_id, $acl)
    {
        $data = [
            'acl' => json_encode($acl->toArray())
        ];

        $result = $this->putJSON($series_id . '/acl', $data, true);

        return $result[1] == 200;
    }
}
