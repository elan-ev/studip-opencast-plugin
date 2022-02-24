<?php
namespace Opencast\Models\REST;

use Opencast\Models\Config;

class ApiSeriesClient extends RestClient
{
    public static $me;
    public $serviceName = "ApiSeries";

    function __construct($config_id = 1)
    {
        if ($config = Config::getConfigForService('apiseries', $config_id)) {
            parent::__construct($config);
        } else {
            throw new \Exception ($this->serviceName . ': '
                . _('Die Opencast-Konfiguration wurde nicht korrekt angegeben'));
        }
    }

    public function getACL($series_id)
    {
        return json_decode(json_encode($this->getJSON('/'.$series_id. '/acl')), true);
    }

    public function setACL($series_id, $acl)
    {
        $data = [
            'acl' => json_encode($acl)
        ];

        $result = $this->putJSON('/' . $series_id . '/acl', $data, true);

        return $result[1] == 200;
    }

    public function getSeries($series_id)
    {
        return $this->getJSON('/'. $series_id);
    }
}
