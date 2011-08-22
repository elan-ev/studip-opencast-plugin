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



        // let's init the services...
        $this->search_conf = OCRestClient::getConfig('search');
        if(isset ($this->search_conf)) {
            if(!fsockopen($this->search_conf['service_url'])) {
                //throw new Exception(_(""))
            } else {
                $this->search_client = new OCRestClient($this->search_conf['service_url'], $this->search_conf['service_user'], $this->search_conf['service_password']);
                $update = 0;
                if($allseries = $this->search_client->getAllSeries()) {
                    foreach ($allseries->seriesList->series as $key => $serie) {
                        if(OCRestClient::storeAllSeries($serie->id)) {
                            $update+=1;
                        }
                    }
                     $this->flash['success'] = $update > 0 ? $success . sprintf(_(" Es wurden %s neue Series gefunden und hinzugefügt."), $update) : $success;
                }
                else {
                    $this->flash['error'] = _("Es besteht momentan keine Verbindung zum Search Service");
                }
            }
        }

        $this->series_conf = OCRestClient::getConfig('series');

        if(isset ($this->series_conf)) {
            if(!fsockopen($this->series_conf['service_url'].'/series')) {
                $this->flash['error'] = _("Es besteht momentan keine Verbindung zum Series Service");
            } else {
                 $this->series_client = new OCRestClient($this->series_conf['service_url'], $this->series_conf['service_user'], $this->series_conf['service_password']);
            }
        }

        $this->captureadmin_conf = OCRestClient::getConfig('captureadmin');
        if(isset ($this->series_conf)) {
            if(!fsockopen($this->series_conf['service_url'].'/capture-admin')) {
                $this->flash['error'] = _("Es besteht momentan keine Verbindung zum Capture-Admin Service");
            } else {
                $this->captureadmin_client = new OCRestClient($this->captureadmin_conf['service_url'], $this->captureadmin_conf['service_user'], $this->captureadmin_conf['service_password']);
            }
        }


        

        // set default layout
        $layout = $GLOBALS['template_factory']->open('layouts/base');
        $this->set_layout($layout);
        

    }

    /**
     * This is the default action of this controller.
     */
    function index_action()
    {

        if (isset($this->flash['message'])) {
            $this->message = $this->flash['message'];
        }
        
        
       /* $this->series = $this->occlient->getAllSeries();
        // We got all series so preserve their ids
        foreach ($this->series as $key => $serie) {
            OCRestClient::storeAllSeries($serie->seriesId[0]);
        } */

    }
    
    function config_action()
    {

        $GLOBALS['CURRENT_PAGE'] =  'OpenCast Administration';
        Navigation::activateItem('/admin/config/oc-config');

      


        if (isset($this->flash['message'])) {
            $this->message = $this->flash['message'];
        }


        if( ($this->search_conf = OCRestClient::getConfig('search')) &&
            ($this->series_conf = OCRestClient::getConfig('series')) &&
            ($this->schedule_conf = OCRestClient::getConfig('schedule')) &&
            ($this->captureadmin_conf = OCRestClient::getConfig('captureadmin')) || true) {

            
            $this->series_url = $this->series_conf['service_url'];
            $this->series_user = $this->series_conf['service_user'];
            $this->series_password = $this->series_conf['service_password'];


            $this->search_url = $this->search_conf['service_url'];
            $this->search_user = $this->search_conf['service_user'];
            $this->search_password = $this->search_conf['service_password'];



            $this->scheduling_url = $this->schedule_conf['service_url'];
            $this->scheduling_user = $this->schedule_conf['service_user'];
            $this->scheduling_password = $this->schedule_conf['service_password'];

            $this->captureadmin_url = $this->captureadmin_conf['service_url'];
            $this->captureadmin_user = $this->captureadmin_conf['service_user'];
            $this->captureadmin_password = $this->captureadmin_conf['service_password'];



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

            $this->captureadmin_url = 'CAPTUREADMIN_ENDPOINT_URL';
            $this->captureadmin_user = 'CAPTUREADMIN_ENDPOINT_USER';
            $this->scheduling_password = '';




          /*  $this->series_url = 'URL_TO_MATTERHORN';
            $this->search_url = 'URL_TO_MATTERHORN';
            $this->user = 'matterhorn_system_account';
            $this->password = 'CHANGE_ME'; */
        }

        // After we've dsiplayed the server settings, we need to display the available resources and the corresponding capture agents
        $this->resources = OCModel::getOCRessources();

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

        $this->captureadmin_url = Request::get('captureadmin_url');
        $this->captureadmin_user = Request::get('captureadmin_user');
        $this->captureadmin_password = Request::get('captureadmin_password');
        
        OCRestClient::setConfig('search', $this->search_url, $this->search_user, $this->search_password);
        OCRestClient::setConfig('series', $this->series_url, $this->series_user, $this->series_password);
        OCRestClient::setConfig('schedule',  $this->scheduling_url, $this->scheduling_user, $this->scheduling_password);
        OCRestClient::setConfig('captureadmin',  $this->captureadmin_url, $this->captureadmin_user, $this->captureadmin_password);

        $success = _("Änderungen wurden erflolgreich übernommen.");


        $this->redirect(PluginEngine::getLink('opencast/admin/config'));
    }

    function resources_action()
    {
        $GLOBALS['CURRENT_PAGE'] =  'OpenCast Administration';
        Navigation::activateItem('/admin/config/oc-resources');

        $this->resources = OCModel::getOCRessources();
        $this->agents = $this->captureadmin_client->getAllCaptureAgents();


        /**
         * TODO (guide for an update_resources_action...):
         *  1st query for new capture agents
         *  2nd compare list with existing agents add new ones...
         *  3rd identify unconnected ca
         *  4th display agents and resources...
         *
         */

    }


    function update_resource_action()
    {
               
        $this->resources = OCModel::getOCRessources();

        foreach($this->resources as $resource) {
            if(($candidate_ca = Request::get($resource['resource_id'])) &&  Request::get('action') == 'add'){
                OCModel::setCAforResource($resource['resource_id'], $candidate_ca);
            }
        }
  

        $this->redirect(PluginEngine::getLink('opencast/admin/resources'));
        
    }
}
?>
