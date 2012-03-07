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
         *	@return array response of episodes
         */
        function getEpisodes($series_id) {
            $service_url = "/search/series.json?id=".$series_id."&episodes=true&series=true";
            if($search = self::getJSON($service_url)){
                $x = "search-results";
                $episodes = $search->$x->result;
                return $episodes;
            } else return false;
        }
    }
?>