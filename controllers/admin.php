<?php
/*
 * admin.php - admin plugin controller
 * Copyright (c) 2010  André Klaßen
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once 'app/controllers/authenticated_controller.php';
require_once $this->trails_root.'/models/OCModel.php';
require_once $this->trails_root.'/models/OCRestClient.php';

class AdminController extends AuthenticatedController
{
    /**
     * Common code for all actions: set default layout and page title.
     */
    function before_filter(&$action, &$args)
    {
        $this->flash = Trails_Flash::instance();
        
        if(($this->search_conf = OCRestClient::getConfig('search')) && ($this->series_conf = OCRestClient::getConfig('series'))) {
            $this->series_client = new OCRestClient($this->series_conf['service_url'], $this->series_conf['user'], $this->series_conf['password']);
            $this->search_client = new OCRestClient($this->search_conf['service_url'], $this->search_conf['user'], $this->search_conf['password']);
        } else {
            throw new Exception(_("Die Verknüpfung  zum Opencast Matterhorn Server wurde nicht korrekt durchgeführt."));
        }


        // set default layout
        $layout = $GLOBALS['template_factory']->open('layouts/base');
        $this->set_layout($layout);
        
        $GLOBALS['CURRENT_PAGE'] =  'OpenCast Administration';
        Navigation::activateItem('/admin/config/opencast');
    }

    /**
     * This is the default action of this controller.
     */
    function index_action()
    {

        if (isset($this->flash['message'])) {
            $this->message = $this->flash['message'];
        }
        
        
        $this->series = $this->occlient->getAllSeries();
        // We got all series so preserve their ids
        foreach ($this->series as $key => $serie) {
            OCRestClient::storeAllSeries($serie->seriesId[0]);
        }
 
    }
    
    function config_action()
    {
        if (isset($this->flash['message'])) {
            $this->message = $this->flash['message'];
        }
        


        if( ($this->search_conf = OCRestClient::getConfig('search')) &&
            ($this->series_conf = OCRestClient::getConfig('series')) &&
            ($this->schedule_conf = OCRestClient::getConfig('schedule')) ) {

            $this->series_url = $this->series_conf['service_url'];
            $this->series_user = $this->series_conf['service_user'];
            $this->series_password = $this->series_conf['service_password'];


            $this->search_url = $this->search_conf['service_url'];
            $this->search_user = $this->search_conf['service_user'];
            $this->search_password = $this->search_conf['service_password'];



            $this->scheduling_url = $this->schedule_conf['service_url'];
            $this->scheduling_user = $this->schedule_conf['service_user'];
            $this->scheduling_password = $this->schedule_conf['service_password'];



        } else {

            $this->search_url = 'SEARCH_ENDPOINT_URL';
            $this->search_user = 'SEARCH_ENDPOINT_USER';
            $this->search_password = '';


            $this->series_url = 'SERIES_ENDPOINT_URL';
            $this->series_user = 'SERIES_ENDPOINT_USER';
            $this->series_password = '';


            $this->scheduling_url = 'SCHEDULE_ENDPOINT_URL';
            $this->scheduling_user = 'SCHEDULE_ENDPOINT_USER';
            $this->scheduling_password = '';




          /*  $this->series_url = 'URL_TO_MATTERHORN';
            $this->search_url = 'URL_TO_MATTERHORN';
            $this->user = 'matterhorn_system_account';
            $this->password = 'CHANGE_ME'; */
        }
       
    }
    function update_config_action() {
        
    
        
        
        
        /*
         * 
         * get all the fancy config stuff
         * 
         * 
         */
        $this->series_url = Request::get('series_url');
        $this->series_user = Request::get('series_user');
        $this->series_password = Request::get('series_password');
        OCRestClient::setConfig($this->config_id, $this->series_url, $this->search_url, $this->user, $this->password);


        $this->search_url = Request::get('search_url');
        $this->search_user = Request::get('search_user');
        $this->search_password = Request::get('search_password');



        $this->scheduling_url = Request::get('scheduling_url');
        $this->scheduling_user = Request::get('scheduling_user');
        $this->scheduling_password = Request::get('scheduling_password');

        OCRestClient::setConfig('search', $this->search_url, $this->search_user, $this->search_password);
        OCRestClient::setConfig('series', $this->series_url, $this->serie_user, $this->series_password);
        OCRestClient::setConfig('schedule',  $this->scheduling_url, $this->scheduling_user, $this->scheduling_password);

        $this->series_url = Request::get('series_url');
        $this->search_url = Request::get('search_url');
        $this->user = Request::get('user');
        $this->password = Request::get('password');
        
        OCRestClient::setConfig($this->config_id, $this->series_url, $this->search_url, $this->user, $this->password);
        $success = _("Änderungen wurden erflolgreich übernommen.");
        
        $update = 0;
        $this->series = $this->occlient->getAllSeries();
        foreach ($this->series as $key => $serie) {
            
            if(OCRestClient::storeAllSeries($serie->seriesId[0])) {
               $update+=1;
            }

        }
        
        $this->flash['success'] = $update > 0 ? $success . sprintf(_(" Es wurden %s neue Series gefunden und hinzugefügt."), $update) : $success;
        
        $this->redirect(PluginEngine::getLink('opencast/admin/config'));
        
        
    }

    function update_action()
    {
        $this->series_url = Request::get('series_url');
        $this->series_user = Request::get('series_user');
        $this->series_password = Request::get('series_password');



        $this->search_url = Request::get('search_url');
        $this->search_user = Request::get('search_user');
        $this->search_password = Request::get('search_password');



        $this->scheduling_url = Request::get('scheduling_url');
        $this->scheduling_user = Request::get('scheduling_user');
        $this->scheduling_password = Request::get('scheduling_password');

        OCRestClient::setConfig('search', $this->search_url, $this->search_user, $this->search_password);
        OCRestClient::setConfig('series', $this->series_url, $this->series_user, $this->series_password);
        OCRestClient::setConfig('schedule',  $this->scheduling_url, $this->scheduling_user, $this->scheduling_password);

        $success = _("Änderungen wurden erflolgreich übernommen.");




        $update = 0;
        $this->series = $this->search_client->getAllSeries();
        foreach ($this->series as $key => $serie) {
           
            if(OCRestClient::storeAllSeries($serie->series->id)) {
               $update+=1;
            }

        }
        
        $this->flash['success'] = $update > 0 ? $success . sprintf(_(" Es wurden %s neue Series gefunden und hinzugefügt."), $update) : $success;


        $this->redirect(PluginEngine::getLink('opencast/admin/config'));
    }
}
?>
