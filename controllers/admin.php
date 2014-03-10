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
require_once $this->trails_root.'/models/OCEndpointModel.php';
require_once $this->trails_root.'/classes/OCRestClient/SearchClient.php';
require_once $this->trails_root.'/classes/OCRestClient/SeriesClient.php';
require_once $this->trails_root.'/classes/OCRestClient/CaptureAgentAdminClient.php';
require_once $this->trails_root.'/classes/OCRestClient/ServicesClient.php';


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

        if (isset($this->flash['message'])) {
            $this->message = $this->flash['message'];
        }

        
        if(($this->info_conf = OCEndpointModel::getBaseServerConf())) {
            $this->info_url = $this->info_conf['service_url'];
            $this->info_user = $this->info_conf['service_user'];
            $this->info_password = $this->info_conf['service_password'];


        }

    }


    function update_action()
    {
        $service_url =  parse_url(Request::get('info_url'));
        $service_host = $service_url['host'] . (isset($service_url['port']) ? ':' . $service_url['port'] : '') ;
        $this->info_url = $service_url['host'] . (isset($service_url['port']) ? ':' . $service_url['port'] : '') .  $service_url['path']; 

        $this->info_user = Request::get('info_user');
        $this->info_password = Request::get('info_password');
        
  
  
        OCRestClient::clearConfig($service_url['host']);
        OCRestClient::setConfig($service_host, $this->info_user, $this->info_password);
        

  
             
        OCEndpointModel::setEndpoint($this->info_url, 'services');
        $services_client = ServicesClient::getInstance();


        $comp = $services_client->getRESTComponents();
        
        $services = OCModel::retrieveRESTservices($comp);

        foreach($services as $service_url => $service_type) {
            $endpoint_url =  parse_url($service_url);
            OCEndpointModel::setEndpoint($endpoint_url['host']. (isset($endpoint_url['port']) ? ':' . $endpoint_url['port'] : ''), $service_type);    
        }


        $success = _("Änderungen wurden erfolgreich übernommen.");

        $this->redirect(PluginEngine::getLink('opencast/admin/config'));
    }
    
    
    function endpoints_action()
    {
        PageLayout::setTitle(_("Opencast Endpoint Verwaltung"));
        Navigation::activateItem('/admin/config/oc-endpoints');
        // hier kann eine Endpointüberischt angezeigt werden.
        //$services_client = ServicesClient::getInstance();
        $this->endpoints = OCEndpointModel::getEndpoints(); 
    }
    
    function update_endpoints_action()
    {    
        $this->redirect(PluginEngine::getLink('opencast/admin/endpoints'));
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
        
        $agents = $caa_client->getCaptureAgents();

        $this->agents = $caa_client->getCaptureAgents();
        foreach ($this->resources as $resource) {
            $assigned_agents = OCModel::getCAforResource($resource['resource_id']);
            if($assigned_agents){
                foreach ($agents->agents->agent as $key => $agent) {
                    if(in_array($agent->name, $assigned_agents)) unset($agents->agents->agent[$key]);
                }
            }
        }
        

        $this->available_agents = $agents;
        
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
