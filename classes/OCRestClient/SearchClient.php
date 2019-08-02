<?php

class SearchClient extends OCRestClient
{
    static $me;
    public $serviceName = 'Search';

    function __construct($config_id = 1)
    {
        if ($config = OCConfig::getConfigForService('search', $config_id)) {
            parent::__construct($config);
        } else {
            throw new Exception (_("Die Konfiguration wurde nicht korrekt angegeben"));
        }
    }

    /**
     *  getEpisodes() - retrieves episode metadata for a given series identifier from conntected Opencast-Matterhorn
     *  Core
     *
     * @param string series_id Identifier for a Series
     *
     * @return array response of episodes
     */
    function getEpisodes($series_id, $refresh = false)
    {
        global $perm;

        $cache = StudipCacheFactory::getCache();
        $cache_key = 'oc_episodesforseries/' . $series_id;
        $episodes = $cache->read($cache_key);

        if ($refresh || $episodes === false || $perm->have_perm('dozent')) {
            $service_url = "/episode.json?sid=" . $series_id . "&q=&episodes=true&sort=&limit=0&offset=0";

            if ($search = $this->getJSON($service_url)) {
                $x = "search-results";
                $episodes = $search->$x->result;
                $cache->write($cache_key, serialize($episodes), 7200);

                return $episodes;
            } else return [];
        } else {
            return unserialize($episodes);
        }
    }

    /**
     *  getSeries() - retrieves episode metadata for a given series identifier from conntected Opencast-Matterhorn Core
     *
     * @param string series_id Identifier for a Series
     *
     * @return array response of series
     */
    function getSeries($series_id)
    {

        $service_url = "/series.json?id=" . $series_id . "&episodes=true&series=true";
        if ($search = $this->getJSON($service_url)) {
            //$x = "search-results";
            //$episodes = $search->$x->result;
            return $search;
        } else return false;
    }

    public function getEpisodesLTI($series_id, $course_id, $roles, $sort='DATE_CREATED_DESC')
    {
        $roles_usable = [];
        foreach ($roles as $role) {
            $roles_usable[] = 'oc_acl_read:' . $course_id . '_' . $role;
        }

        $cookie = OpencastLTI::launch_lti($GLOBALS['user']->id, $course_id, $series_id);

        $special_query = 'dc_is_part_of:'. $series_id .' AND ( '. implode(' OR ', $roles_usable) .' )';
        $service_url = "/lucene.json?q=$special_query&sort=$sort&limit=20&offset=0&admin=false";

        $service_url = str_replace(' ','%20',$service_url);
        $service_url = str_replace(':','%3A',$service_url);

        $this->setCookie('JSESSIONID', $cookie);
        $result = $this->getJSON($service_url);

        if ($result) {
            $x = "search-results";
            $episodes = $result->$x->result;

            return $episodes;
        }

        return [];
    }

    /**
     *  getAllSeries() - retrieves episode metadata for a given series identifier from conntected Opencast-Matterhorn
     *  Core
     *
     * @param void
     *
     * @return array response of series
     */
    function getAllSeries()
    {

        $service_url = "/series.json";
        if ($series = $this->getJSON($service_url)) {
            //$x = "search-results";
            //$episodes = $search->$x->result;
            return $series;
        } else return false;
    }




    // other functions

    /**
     *  getEpisodeCount -
     *
     * @param string series_id Identifier for a Series
     *
     * @return int number of episodes
     */
    function getEpisodeCount($series_id)
    {
        if ($series = $this->getSeries($series_id)) {
            $x = "search-results";
            $count = $series->$x->total;

            return intval($count);
        } else return false;

    }


    function getBaseURL()
    {
        $base = $this->base_url;
        $url = preg_replace('/\/search/', '', $base);
        $url = str_replace('http://', 'https://', $url);

        return $url;
    }


}
