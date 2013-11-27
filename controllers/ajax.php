<?php

require_once 'app/controllers/studip_controller.php';
require_once $this->trails_root.'/classes/OCRestClient/SearchClient.php';
require_once $this->trails_root.'/classes/OCRestClient/SeriesClient.php';

class AjaxController extends StudipController
{

    function before()
    {

    }

    function index_action()
    {
        $this->render_text(_("Ups.."));
    }
    
    function getSeries_action() {
        
        $allseries = OCSeriesModel::getAllSeries();
        $this->render_text(json_encode($allseries));
    }
    
    function getEpisodes_action($series_id) {
        $search_client = SearchClient::getInstance();
        $episodes = $search_client->getEpisodes($series_id);
        $this->render_text(json_encode($episodes));
        
    }
    
}
