<?php
    require_once "OCRestClient.php";
    class SearchClient extends OCRestClient
    {
        function __construct() {

            if ($config = parent::getConfig('search')) {
                parent::__construct($config['service_url'],
                                    $config['service_user'],
                                    $config['service_password']);
            } else {
                throw new Exception (_("Die Searchservice Konfiguration wurde nicht im gültigen Format angegeben."));
            }
        }

        /**
         *  getEpisodes() - retrieves episode metadata for a given series identifier from conntected Opencast-Matterhorn Core
         *
         *  @param string series_id Identifier for a Series
         *
         *  @return array response of episodes
         */
        function getEpisodes($series_id) {

            $service_url = "/search/series.json?id=".$series_id."&episodes=true&series=true&limit=0&offset=0";

            if($search = self::getJSON($service_url)){
                $x = "search-results";
                $episodes = $search->$x->result;
                return $episodes;
            } else return false;
        }

        /**
         *  getSeries() - retrieves episode metadata for a given series identifier from conntected Opencast-Matterhorn Core
         *
         *  @param string series_id Identifier for a Series
         *
         *  @return array response of series
         */
        function getSeries($series_id) {

            $service_url = "/search/series.json?id=".$series_id."&episodes=true&series=true";
            if($search = self::getJSON($service_url)){
                //$x = "search-results";
                //$episodes = $search->$x->result;
                return $search;
            } else return false;
        }

        // other functions

        /**
         *  getEpisodeCount -
         *
         *  @param string series_id Identifier for a Series
         *
         *  @return int number of episodes
         */
        function getEpisodeCount($series_id) {
            if($series = self::getSeries($series_id)) {
                $x = "search-results";
                $count = $series->$x->total;
                return intval($count);
            } else return false;

        }


    }
?>