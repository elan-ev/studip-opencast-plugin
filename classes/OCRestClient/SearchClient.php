<?php

use Opencast\Models\OCConfig;
use Opencast\Models\Pager;

class SearchClient extends OCRestClient
{
    public static $me;
    public $serviceName = 'Search';

    public function __construct($config_id = 1)
    {
        if ($config = OCConfig::getConfigForService('search', $config_id)) {
            parent::__construct($config);
        } else {
            throw new Exception (_('Die Konfiguration wurde nicht korrekt angegeben'));
        }
    }

    /**
     *  getSeries() - retrieves episode metadata for a given series identifier from conntected Opencast-Matterhorn Core
     *
     * @param string series_id Identifier for a Series
     *
     * @return array response of series
     */
    public function getSeries($series_id)
    {
        $service_url = "/series.json?id={$series_id}&episodes=true&series=true";
        if ($search = $this->getJSON($service_url)) {
            //$x = "search-results";
            //$episodes = $search->$x->result;
            return $search;
        }
    }

    /**
     *  getAllSeries() - retrieves episode metadata for a given series identifier from conntected Opencast-Matterhorn
     *  Core
     *
     * @param void
     *
     * @return array response of series
     */
    public function getAllSeries()
    {
        $service_url = "/series.json?limit=10000";

        if ($series = $this->getJSON($service_url)) {
            $x = "search-results";

            if (is_array($series->$x->result)) {
                return $series->$x->result;
            } else {
                return [$series->$x->result];
            }
        } else {
            return false;
        }
    }

    public function getBaseURL()
    {
        $base = $this->base_url;
        $url = preg_replace('/\/search/', '', $base);

        return $url;
    }
}
