<?php

use Opencast\Models\OCConfig;

class ApiSeriesClient extends OCRestClient
{
    public static $me;
    public $serviceName = "ApiSeries";

    function __construct($config_id = 1)
    {
        if ($config = OCConfig::getConfigForService('apiseries', $config_id, '/api/series')) {
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
            'acl' => json_encode(is_array($acl) ? $acl : $acl->toArray())
        ];

        $result = $this->putJSON('/' . $series_id . '/acl', $data, true);

        return $result[1] == 200;
    }

    public function search($text)
    {
        if (strlen($text) < 3) {
            return false;
        }

        $params = [
            'filter'              => 'textFilter:' . $text,
            'limit'               => 100,
            'offset'              => 0,
            'withacl'             => 'false',
            'onlyWithWriteAccess' => 'false'
        ];

        $data = $this->getJSON($query = '/?' . http_build_query($params));

        $series = [];

        if (is_array($data)) foreach ($data as $serie) {
            $series[$serie->identifier] = $serie->title;
        }

        return $series;
    }
}
