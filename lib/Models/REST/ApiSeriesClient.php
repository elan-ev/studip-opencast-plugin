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

    /**
     * Retrieves series ACL from connected Opencast
     * 
     * @param string $series_id id of series
     * 
     * @return array|bool
     */
    public function getACL($series_id)
    {
        $response = $this->opencastApi->seriesApi->getAcl($series_id);
        if ($response['code'] == 200) {
            return $response['body'];
        }

        return false;
    }

    /**
     * Sets ACL for a series in connected Opencast
     * 
     * @param string $series_id id of series
     * @param object $acl the acl object
     * 
     * @return boolean
     */
    public function setACL($series_id, $acl)
    {
        $response = $this->opencastApi->seriesApi->updateAcl($series_id, $acl);
        return $response['code'] == 200;
    }

    /**
     * Get the series from connected opencsat
     * 
     * @param string $series_id id of series
     * 
     * @return object|bool series object or false if unable to get.
     */
    public function getSeries($series_id)
    {
        $response = $this->opencastApi->seriesApi->get($series_id);
        if ($response['code'] == 200) {
            return $response['body'];
        }

        return false;
    }
}
