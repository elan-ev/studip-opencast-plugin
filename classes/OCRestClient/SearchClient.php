<?php

use Opencast\Models\OCConfig;

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
     *  getEpisodes() - retrieves episode metadata for a given series identifier
     *  from connected Opencast
     *
     * @param string series_id Identifier for a Series
     *
     * @return array response of episodes
     */
    public function getEpisodes($series_id, $refresh = false)
    {
        $cache = StudipCacheFactory::getCache();
        $cache_key = 'oc_episodesforseries/' . $series_id;
        $episodes = $cache->read($cache_key);

        if ($refresh || $episodes === false || $GLOBALS['perm']->have_perm('dozent')) {
            $service_url = "/episode.json?sid=" . $series_id . "&q=&episodes=true&sort=&limit=0&offset=0";
            $x = "search-results";

            if ($search = $this->getJSON($service_url)) {
                $episodes = $search->$x->result;

                if (!is_array($episodes)) {
                    $episodes = [$episodes];
                }

                $cache->write($cache_key, serialize($episodes), 7200);
                return $episodes ?: [];
            } else {
                return [];
            }
        } else {
            return unserialize($episodes) ?: [];
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
        $service_url = "/series.json";

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




    // other functions

    /**
     *  getEpisodeCount -
     *
     * @param string series_id Identifier for a Series
     *
     * @return int number of episodes
     */
    public function getEpisodeCount($series_id)
    {
        if ($series = $this->getSeries($series_id)) {
            $x = "search-results";
            $count = $series->$x->total;

            return intval($count);
        }
    }

    public function getBaseURL()
    {
        $base = $this->base_url;
        $url = preg_replace('/\/search/', '', $base);

        return $url;
    }
}
