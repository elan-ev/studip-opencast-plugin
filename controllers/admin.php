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
require_once $this->trails_root.'/classes/OCRestClient/SearchClient.php';
require_once $this->trails_root.'/classes/OCRestClient/SeriesClient.php';
require_once $this->trails_root.'/classes/OCRestClient/CaptureAgentAdminClient.php';
require_once $this->trails_root.'/classes/OCRestClient/InfoClient.php';


class AdminController extends AuthenticatedController
{
    /**
     * Common code for all actions: set default layout and page title.
     */
    function before_filter(&$action, &$args)
    {
        
        $this->flash = Trails_Flash::instance();

        // set default layout
        $layout = $GLOBALS['template_factory']->open('layouts/base');
        $this->set_layout($layout);

    }

    /**
     * This is the default action of this controller.
     */
    function index_action()
    {
        $this->redirect(PluginEngine::getLink('opencast/admin/config'));
    }

    function config_action()
    {
        PageLayout::setTitle(_("Opencast Administration"));
        Navigation::activateItem('/admin/config/oc-config');


       // $this->info_conf = OCRestClient::setConfig('info', 'vm283.rz.uos.de:8080', 'matterhorn_system_account', 'CHANGE_ME');
 

        if (isset($this->flash['message'])) {
            $this->message = $this->flash['message'];
        }


        /**
         * TODO: add generic mechanism for the assignment of config params
         *
         */
        if( ($this->info_conf = OCRestClient::getConfig('info'))) {


            $this->info_url = $this->info_conf['service_url'];
            $this->info_user = $this->info_conf['service_user'];
            $this->info_password = $this->info_conf['service_password'];


        }

    }


    function update_action()
    {
        $service_url =  parse_url(Request::get('info_url'));
        $this->info_url = $service_url['host'] . (isset($service_url['port']) ? ':' . $service_url['port'] : '') .  $service_url['path']; 

        $this->info_user = Request::get('info_user');
        $this->info_password = Request::get('info_password');
        
        OCRestClient::setConfig('info', $this->info_url, $this->info_user, $this->info_password);
       
        $info_client    = InfoClient::getInstance();
        $comp = $info_client->getRESTComponents();
        
        $services = OCModel::retrieveRESTservices($comp);
        OCRestClient::clearConfig();
        
        foreach($services as $service_type => $service_url) {
            OCRestClient::setConfig($service_type, $service_url, $this->info_user, $this->info_password);
        
        }

        $success = _("Änderungen wurden erfolgreich übernommen.");

        $this->redirect(PluginEngine::getLink('opencast/admin/config'));
    }
    /**
     * brings REST URL in one format before writing in db
     */
    function cleanClientURLs()
    {
        $urls = array('series', 'search', 'scheduling', 'ingest', 'captureadmin'
            , 'upload', 'mediapackage');
            
        foreach($urls as $pre) {
            $var = $pre.'_url';
            $this->$var = rtrim($this->$var,"/");
        }
        
    }

    function resources_action()
    {
        PageLayout::setTitle(_("Opencast Capture Agent Verwaltung"));
        Navigation::activateItem('/admin/config/oc-resources');
        
        $this->resources = OCModel::getOCRessources();
        if(empty($this->resources)) {
            $this->flash['info'] = _('Es wurden keine passenden Ressourcen gefunden.');

        }

        $caa_client = CaptureAgentAdminClient::getInstance();
        $this->agents = $caa_client->getCaptureAgents();
        $this->assigned_cas = OCModel::getAssignedCAS();

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

    function remove_ca_action($resource_id, $capture_agent)
    {
        OCModel::removeCAforResource($resource_id, $capture_agent);
        $this->redirect(PluginEngine::getLink('opencast/admin/resources'));
    }

    // client status
    function client_action()
    {
        $caa_client    = CaptureAgentAdminClient::getInstance();
        $this->agents  = $caa_client->getCaptureAgents();
    }
}
?>
