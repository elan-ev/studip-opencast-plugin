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
require_once $this->trails_root.'/classes/OCRestClient/WorkflowClient.php';


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
        
        // notify on trails action
        $klass = substr(get_called_class(), 0, -10);
        $name = sprintf('oc_admin.performed.%s_%s', $klass, $action);
        NotificationCenter::postNotification($name, $this);

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


        
        if(($this->info_conf = OCEndpointModel::getBaseServerConf())) {
            $this->info_url = $this->info_conf['service_url'];
            $this->info_user = $this->info_conf['service_user'];
            $this->info_password = $this->info_conf['service_password'];


        }

    }


    function update_action()
    {
        $service_url =  parse_url(Request::get('info_url'));

        if(!array_key_exists('scheme', $service_url)) {
            $this->flash['messages'] = array('error' => _('Es wurde kein gültiges URL-Schema angegeben.'));
            OCRestClient::clearConfig($service_url['host']);
            $this->redirect(PluginEngine::getLink('opencast/admin/config'));
        } else {
            $service_host = $service_url['scheme'] .'://' . $service_url['host'] . (isset($service_url['port']) ? ':' . $service_url['port'] : '') ;
            $this->info_url = $service_url['host'] . (isset($service_url['port']) ? ':' . $service_url['port'] : '') .  $service_url['path']; 
        

            $this->info_user = Request::get('info_user');
            $this->info_password = Request::get('info_password');
  
            OCRestClient::clearConfig($service_url['host']);
            OCRestClient::setConfig($service_host, $this->info_user, $this->info_password);
             
            OCEndpointModel::setEndpoint($this->info_url, 'services');
            $services_client = ServicesClient::getInstance();


            $comp = $services_client->getRESTComponents();
            if($comp) {
                $services = OCModel::retrieveRESTservices($comp);


                foreach($services as $service_url => $service_type) {

                    $service_comp = explode("/", $service_url);
            
                    if(sizeof($service_comp) == 2) {
                        if($service_comp)
                        OCEndpointModel::setEndpoint($service_comp[0], $service_type);
                    }   
                }


                $this->flash['messages'] = array('success' => sprintf(_("Änderungen wurden erfolgreich übernommen. Es wurden %s Endpoints für die angegeben Opencast Matterhorn Installation gefunden und in der Stud.IP Konfiguration eingetragen"), count($comp)));
            } else {
                $this->flash['messages'] = array('error' => _('Es wurden keine Endpoints für die angegeben Opencast Matterhorn Installation gefunden. Überprüfen Sie bitte die eingebenen Daten.'));
            }

            $this->redirect(PluginEngine::getLink('opencast/admin/config'));
        }
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
            $this->flash['messages'] =array('info' => _('Es wurden keine passenden Ressourcen gefunden.'));

        }

        $caa_client = CaptureAgentAdminClient::getInstance();
        $workflow_client = WorkflowClient::getInstance();
        
        $agents = $caa_client->getCaptureAgents();
        $this->agents = $caa_client->getCaptureAgents();

        foreach ($this->resources as $resource) {
            $assigned_agents = OCModel::getCAforResource($resource['resource_id']);
            if($assigned_agents){

                foreach($agents->agents as $key => $agent) {
                    if(in_array($agent->name, $assigned_agents)) unset($agents->agents->$key);
                    else{
                        OCModel::removeCAforResource($resource['resource_id'], $assigned_agents['capture_agent']);
                    }
                }
            }
        }

        $this->available_agents = $agents;
        $this->definitions = $workflow_client->getDefinitions();

        $this->assigned_cas = OCModel::getAssignedCAS();

    }


    function update_resource_action()
    {

        $this->resources = OCModel::getOCRessources();

        foreach($this->resources as $resource) {
            if(Request::get('action') == 'add'){
                if(($candidate_ca = Request::get($resource['resource_id'])) && $candidate_wf = Request::get('workflow')){
                    OCModel::setCAforResource($resource['resource_id'], $candidate_ca, $candidate_wf);
                }
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
