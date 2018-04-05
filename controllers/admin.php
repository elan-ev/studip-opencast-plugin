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

require_once $this->trails_root.'/models/OCModel.php';
require_once $this->trails_root.'/models/OCEndpointModel.php';
require_once $this->trails_root.'/classes/OCRestClient/SearchClient.php';
require_once $this->trails_root.'/classes/OCRestClient/SeriesClient.php';
require_once $this->trails_root.'/classes/OCRestClient/CaptureAgentAdminClient.php';
require_once $this->trails_root.'/classes/OCRestClient/ServicesClient.php';
require_once $this->trails_root.'/classes/OCRestClient/WorkflowClient.php';


class AdminController extends OpencastController
{
    /**
     * Constructs the controller and provide translations methods.
     *
     * @param object $dispatcher
     * @see https://stackoverflow.com/a/12583603/982902 if you need to overwrite
     *      the constructor of the controller
     */
    public function __construct($dispatcher)
    {
        parent::__construct($dispatcher);

        $this->plugin = $dispatcher->current_plugin;

        // Localization
        $this->_ = function ($string) use ($dispatcher) {
            return call_user_func_array(
                [$dispatcher->current_plugin, '_'],
                func_get_args()
            );
        };

        $this->_n = function ($string0, $tring1, $n) use ($dispatcher) {
            return call_user_func_array(
                [$dispatcher->current_plugin, '_n'],
                func_get_args()
            );
        };
    }

    /**
     * Intercepts all non-resolvable method calls in order to correctly handle
     * calls to _ and _n.
     *
     * @param string $method
     * @param array  $arguments
     * @return mixed
     * @throws RuntimeException when method is not found
     */
    public function __call($method, $arguments)
    {
        $variables = get_object_vars($this);
        if (isset($variables[$method]) && is_callable($variables[$method])) {
            return call_user_func_array($variables[$method], $arguments);
        }
        throw new RuntimeException("Method {$method} does not exist");
    }

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
        $this->redirect('admin/config');
    }

    function config_action()
    {
        PageLayout::setTitle($this->_("Opencast Administration"));
        Navigation::activateItem('/admin/config/oc-config');



        if(($this->info_conf = OCEndpointModel::getBaseServerConf(1))) {
            $this->info_url = $this->info_conf['service_url'];
            $this->info_user = $this->info_conf['service_user'];
            $this->info_password = $this->info_conf['service_password'];
        }
        if(($this->slave_conf = OCEndpointModel::getBaseServerConf(2))) {
            $this->slave_url = $this->slave_conf['service_url'];
            $this->slave_user = $this->slave_conf['service_user'];
            $this->slave_password = $this->slave_conf['service_password'];


        }

    }


    function update_action()
    {
        $service_url =  parse_url(Request::get('info_url'));
        $config_id = 1; // we assume that we want to configure the new opencast

        if (!array_key_exists('scheme', $service_url)) {
            $this->flash['messages'] = array('error' => $this->_('Es wurde kein gültiges URL-Schema angegeben.'));
            OCRestClient::clearConfig($config_id);
            $this->redirect('admin/config');
        } else {
            $service_host = $service_url['scheme'] .'://' . $service_url['host'] . (isset($service_url['port']) ? ':' . $service_url['port'] : '') ;
            $this->info_url = $service_url['host'] . (isset($service_url['port']) ? ':' . $service_url['port'] : '') .  $service_url['path'];
            $this->info_user = Request::get('info_user');
            $this->info_password = Request::get('info_password');

            OCRestClient::clearConfig($config_id);
            OCRestClient::setConfig($config_id, $service_host, $this->info_user, $this->info_password);
            OCEndpointModel::setEndpoint($config_id, $service_host .'/services', 'services');

            $services_client = new ServicesClient($config_id);
            $comp = $services_client->getRESTComponents();

            if ($comp) {
                $services = OCModel::retrieveRESTservices($comp, $service_url['scheme']);

                foreach($services as $service_url => $service_type) {
                    OCEndpointModel::setEndpoint($config_id, $service_url, $service_type);
                }

                $success_message = sprintf(
                    $this->_("Änderungen wurden erfolgreich übernommen. Es wurden %s Endpoints für die angegeben Opencast Matterhorn Installation gefunden und in der Stud.IP Konfiguration eingetragen"),
                    count($services)
                );

                $this->flash['messages'] = array('success' => $success_message);
            } else {
                $this->flash['messages'] = array('error' => $this->_('Es wurden keine Endpoints für die angegeben Opencast Matterhorn Installation gefunden. Überprüfen Sie bitte die eingebenen Daten.'));
            }
        }

        $redirect = true;

        if (!empty(Request::get('slave_url'))) {
        // stupid duplication for slave-config
        $slave_url =  parse_url(Request::get('slave_url'));
        $config_id = 2; // we assume that we want to configure slave opencast server

        if (!array_key_exists('scheme', $slave_url)) {
                $this->flash['messages'] = array('error' => $this->_('Es wurde kein gültiges URL-Schema für den Lesezugriff angegeben.'));
            OCRestClient::clearConfig($config_id);
            //$this->redirect('admin/config');
            } else {
            $slave_host           = $slave_url['scheme'] .'://' . $slave_url['host'] . (isset($slave_url['port']) ? ':' . $slave_url['port'] : '') ;
            $this->slave_url      = $slave_url['host'] . (isset($slave_url['port']) ? ':' . $slave_url['port'] : '') .  $slave_url['path'];
            $this->slave_user     = Request::get('slave_user');
            $this->slave_password = Request::get('slave_password');

            OCRestClient::clearConfig($config_id);
            OCRestClient::setConfig($config_id, $slave_host, $this->slave_user, $this->slave_password);
            OCEndpointModel::setEndpoint($config_id, $slave_host .'/services', 'services');

            //fix client call here for new config
            $services_client2 = new ServicesClient($config_id);
            $comp = $services_client2->getRESTComponents();

            if ($comp) {
                $services = OCModel::retrieveRESTservices($comp, $service_url['scheme']);

                foreach($services as $service_url => $service_type) {
                    OCEndpointModel::setEndpoint($config_id, $service_url, $service_type);
                }

                $this->flash['messages'] = array('success' => $success_message . " " . sprintf($this->_("Es wurden %s Endpoints für die angegebene Opencast Slave Installation gefunden und eingetragen"), count($comp)));
            } else {
                $this->flash['messages'] = array('error' => $this->_('Es wurden keine Endpoints für die angegebene Opencast Slave Installation gefunden. Überprüfen Sie bitte die eingebenen Daten.'));
                }
            }
        }

        // after updating the configuration, clear the cached series data
        OCSeriesModel::clearCachedSeriesData();

        if($redirect) {
            $this->redirect('admin/config');
        }
    }


    function endpoints_action()
    {
        PageLayout::setTitle($this->_("Opencast Endpoint Verwaltung"));
        Navigation::activateItem('/admin/config/oc-endpoints');
        // hier kann eine Endpointüberischt angezeigt werden.
        //$services_client = ServicesClient::getInstance();
        $this->endpoints = OCEndpointModel::getEndpoints();
    }

    function update_endpoints_action()
    {
        $this->redirect('admin/endpoints');
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
        PageLayout::setTitle($this->_("Opencast Capture Agent Verwaltung"));
        Navigation::activateItem('/admin/config/oc-resources');

        $this->resources = OCModel::getOCRessources();
        if(empty($this->resources)) {
            $this->flash['messages'] = array('info' => $this->_('Es wurden keine passenden Ressourcen gefunden.'));

        }

        $caa_client = CaptureAgentAdminClient::getInstance();
        $workflow_client = WorkflowClient::getInstance();

        $agents = $caa_client->getCaptureAgents();
        $this->agents = $caa_client->getCaptureAgents();


        foreach ($this->resources as $resource) {
            $assigned_agents = OCModel::getCAforResource($resource['resource_id']);
            if($assigned_agents){
                $existing_agent = false;
                foreach($agents as $key => $agent) {
                    if($agent->name ==  $assigned_agents['capture_agent']) {
                        unset($agents->$key);
                        $existing_agent = true;
                    }
                 }
                if(!$existing_agent){
                    OCModel::removeCAforResource($resource['resource_id'], $assigned_agents['capture_agent']);
                    $this->flash['messages'] = array('info' => sprintf($this->_("Der Capture Agent %s existiert nicht mehr und wurde entfernt."),$assigned_agents['capture_agent'] ));
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
                    $success = OCModel::setCAforResource($resource['resource_id'], $candidate_ca, $candidate_wf);
                }
            }
        }

        if($success) $this->flash['messages'] = array('success' => $this->_("Capture Agents wurden zugewiesen."));

        $this->redirect('admin/resources');
    }

    function remove_ca_action($resource_id, $capture_agent)
    {
        OCModel::removeCAforResource($resource_id, $capture_agent);
        $this->redirect('admin/resources');
    }

    // client status
    function client_action()
    {
        $caa_client    = CaptureAgentAdminClient::getInstance();
        $this->agents  = $caa_client->getCaptureAgents();
    }

    function refresh_episodes_action($ticket){
        if(check_ticket($ticket) && $GLOBALS['perm']->have_studip_perm('admin',$this->course_id)) {
            $stmt = DBManager::get()->prepare("SELECT DISTINCT ocs.seminar_id, ocs.series_id FROM oc_seminar_series AS ocs WHERE 1");
            $stmt->execute(array());
            $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (is_array($courses)) {
                foreach ($courses as $course) {

                    $ocmodel = new OCCourseModel($course['seminar_id']);
                    $ocmodel->getEpisodes(true);
                    unset($ocmodel);
                }
                $this->flash['messages'] = array('success' => $this->_("Die Episodenliste aller Series  wurde aktualisiert."));
            }
        }
        $this->redirect('admin/config/');
    }
}
?>
