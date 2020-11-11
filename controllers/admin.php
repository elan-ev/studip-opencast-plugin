<?php
use Opencast\Models\OCConfig;
use Opencast\Models\OCEndpoints;
use Opencast\Models\OCSeminarSeries;
use Opencast\LTI\OpencastLTI;
use Opencast\Constants;
use Opencast\Configuration;

class AdminController extends OpencastController
{
    /**
     * This is the default action of this controller.
     */
    public function index_action()
    {
        $this->redirect('admin/config');
    }

    public function config_action()
    {
        PageLayout::setTitle($this->_('Opencast Administration'));
        Navigation::activateItem('/admin/config/oc-config');

        $this->config = OCConfig::getBaseServerConf();
    }

    public function clear_series_action()
    {
        return;
        set_time_limit(7200);

        if (!$GLOBALS['perm']->have_perm('root')) {
            throw new AccessDeniedException();
        }

        $series_client = SeriesClient::getInstance(1);
        $apiseries     = ApiSeriesClient::getInstance(1);

        $series = $series_client->getJSON('/allSeriesIdTitle.json');

        $acl = '[
              {
                "allow": true,
                "role": "ROLE_USER_ADMIN",
                "action": "read"
              },
              {
                "allow": true,
                "role": "ROLE_USER_ADMIN",
                "action": "write"
              }
            ]';

        // set default acl for all series, with override
        foreach ($series->series as $ser) {
            $series_id = $ser->identifier;

            echo '<b>' . $ser->title . '</b><br/>';
            var_dump($apiseries->putJSON('/' . $series_id . '/acl', [
                'acl'      => $acl,
                'override' => 'true'
            ]));
        }

        echo '<hr><hr>';

        foreach (OCSeminarSeries::findBySQL(1) as $data) {
            $seminar_id = $data->seminar_id;
            var_dump($seminar_id);
            var_dump(OpencastLTI::setAcls($seminar_id));
        }
        die;
    }

    public function world_series_action()
    {
        return;

        set_time_limit(7200);

        if (!$GLOBALS['perm']->have_perm('root')) {
            throw new AccessDeniedException();
        }

        $api        = ApiWorkflowsClient::getInstance(1);
        $api_events = ApiEventsClient::getInstance(1);

        $fd = fopen($filename = __DIR__ . '/episodes.txt', 'r');

        while ($episode_id = fgets($fd)) {
            $episode_id = str_replace("\n", '', $episode_id);
            echo 'Verarbeite ' . $episode_id . "...\n";
            $api_events->postJson('/' . $episode_id . '/acl/read', ['role' => 'ROLE_ANONYMOUS']);
            $api->republish($episode_id);
        }

        fclose($fd);

        die;
    }

    private function getOCBaseVersion($service_host, $username, $password)
    {
        $oc = new OCRestClient([
            'service_url'      => $service_host,
            'service_user'     => $username,
            'service_password' => $password
        ]);

        // for versions < 5
        $data = $oc->getJSON('/sysinfo/bundles/version?prefix=matterhorn');

        // for versions > 4 (name was changed to opencast after that)
        if (!$data) {
            $data = $oc->getJSON('/sysinfo/bundles/version?prefix=opencast');
        }

        // always use the first found version information
        if (is_array($data->versions)) {
            $data = $data->versions[0];
        }

        return (int)substr($data->version, 0, 1);
    }

    public function update_action()
    {
        // invalidate series-cache when editing configuration
        StudipCacheFactory::getCache()->expire('oc_allseries');

        // update global config
        $request = Request::getArray('config');

        // set global config options
        foreach ($request['global'] as $option => $value) {
            Configuration::setGlobalConfig($option, $value);
        }
        unset($request['global']);

        // set tos (if any)
        if (Request::get('tos')) {
            Configuration::setGlobalConfig(OPENCAST_TOS, Request::get('tos'));
        }

        foreach ($request as $config_id => $config) {
            $configuration = Configuration::instance($config_id);

            foreach ($config as $name => $value) {
                if (in_array($name, ['url', 'user', 'password']) === true) continue;
                $configuration[$name] = $value;
            }

            $configuration->store();

            // if no data is given (i.e.: The selected config shall be deleted!),
            // remove config data properly
            if (!$config['url']) {
                OCConfig::clearConfigAndAssociatedEndpoints($config_id);
                continue;
            }

            $service_url = parse_url($config['url']);

            // check the selected url for validity
            if (!array_key_exists('scheme', $service_url)) {
                PageLayout::postError(sprintf(
                    $this->_('Ungültiges URL-Schema: "%s"'),
                    htmlReady($config['url'])
                ));
                OCConfig::clearConfigAndAssociatedEndpoints($config_id);
            } else {
                $service_host =
                    $service_url['scheme'] . '://' .
                    $service_url['host'] .
                    (isset($service_url['port']) ? ':' . $service_url['port'] : '');

                try {
                    $version = $this->getOCBaseVersion($service_host, $config['user'], $config['password']);

                    OCConfig::clearConfigAndAssociatedEndpoints($config_id);
                    OCConfig::setConfig($config_id, $service_host, $config['user'], $config['password'], $version);
                    $configuration->store();

                    // check, if the same url has been provided for multiple oc-instances
                    foreach (Request::getArray('config') as $zw_id => $zw_conf) {
                        if ($zw_id != $config_id && $zw_conf['url'] == $config['url']) {
                            PageLayout::postError(sprintf(
                                $this->_('Sie haben mehr als einmal dieselbe URL für eine Opencast Installation angegeben.
                                        Dies ist jedoch nicht gestattet. Bitte korrigieren Sie Ihre Eingaben. URL: "%s"'),
                                htmlReady($config['url'])
                            ));
                            continue 2;
                        }
                    }
                    OCEndpoints::setEndpoint($config_id, $service_host . '/services', 'services');
                    $services_client = new ServicesClient($config_id);
                    $comp            = null;
                    $comp            = $services_client->getRESTComponents();
                } catch (AccessDeniedException $e) {
                    OCEndpoints::removeEndpoint($config_id, 'services');
                    PageLayout::postError(sprintf(
                        $this->_('Fehlerhafte Zugangsdaten für die Opencast Installation mit der URL "%s". Überprüfen Sie bitte die eingebenen Daten.'),
                        htmlReady($service_host)
                    ));
                    $this->redirect('admin/config');
                    return;
                }

                if ($comp) {
                    $services = OCModel::retrieveRESTservices($comp, $service_url['scheme']);
                    if (empty($services)) {
                        OCEndpoints::removeEndpoint($config_id, 'services');
                        PageLayout::postError(sprintf(
                            $this->_('Es wurden keine Endpoints für die Opencast Installation mit der URL "%s" gefunden.
                                Überprüfen Sie bitte die eingebenen Daten, achten Sie dabei auch auf http vs https und
                                ob ihre Opencast-Installation https unterstützt.'),
                            htmlReady($service_host)
                        ));
                    } else {
                        foreach ($services as $service_url => $service_type) {
                            if (in_array(strtolower($service_type), Opencast\Constants::$SERVICES) !== false) {
                                OCEndpoints::setEndpoint($config_id, $service_url, $service_type);
                            } else {
                                unset($services[$service_url]);
                            }
                        }
                        PageLayout::postSuccess(sprintf(
                            $this->_('Die Opencast Installation "%s" wurde erfolgreich konfiguriert.'),
                            htmlReady($service_host)
                        ));
                    }
                } else {
                    OCEndpoints::removeEndpoint($config_id, 'services');
                    PageLayout::postError(sprintf(
                        $this->_('Es wurden keine Endpoints für die Opencast Installation mit der URL "%s" gefunden. Überprüfen Sie bitte die eingebenen Daten.'),
                        htmlReady($service_host)
                    ));
                }
            }
        }

        // after updating the configuration, clear the cached series data
        OCSeriesModel::clearCachedSeriesData();
        #OpencastLTI::generate_complete_acl_mapping();

        $this->redirect('admin/config');
    }

    public function endpoints_action()
    {
        PageLayout::setTitle($this->_('Opencast Endpoint Verwaltung'));
        // Navigation::activateItem('/admin/config/oc-endpoints');

        $this->configs   = OCConfig::getBaseServerConf();
        $this->endpoints = OCEndpoints::getEndpoints();
    }

    public function resources_action()
    {
        PageLayout::setTitle($this->_('Opencast Capture Agent Verwaltung'));
        Navigation::activateItem('/admin/config/oc-resources');

        $this->resources = OCModel::getOCRessources();
        if (empty($this->resources)) {
            PageLayout::postInfo($this->_('Es wurden keine passenden Ressourcen gefunden.'));
        }

        $caa_client      = CaptureAgentAdminClient::getInstance();
        $workflow_client = WorkflowClient::getInstance();
        $agents          = $caa_client->getCaptureAgents();
        $this->agents    = $agents;

        foreach ($this->resources as $resource) {
            $assigned_agents = OCModel::getCAforResource($resource['resource_id']);

            if ($assigned_agents) {
                $existing_agent = false;

                foreach ($agents as $key => $agent) {
                    if ($agent->name == $assigned_agents['capture_agent']) {
                        unset($agents[$key]);
                        $existing_agent = true;
                    }
                }

                if (!$existing_agent) {
                    OCModel::removeCAforResource($resource['resource_id'], $assigned_agents['capture_agent']);
                    PageLayout::postInfo(sprintf(
                        $this->_('Der Capture Agent %s existiert nicht mehr und wurde entfernt.'),
                        htmlReady($assigned_agents['capture_agent'])
                    ));
                }
            }
        }

        $this->available_agents = $agents;
        $this->definitions      = $workflow_client->getDefinitions();
        $this->assigned_cas     = OCModel::getAssignedCAS();
        $this->workflows        = array_filter(
            $workflow_client->getTaggedWorkflowDefinitions(),
            function ($element) {
                return (in_array('upload', $element['tags']) !== false)
                    ? $element
                    : false;
            }
        );

        $this->current_workflow = OCCourseModel::getWorkflowWithCustomCourseID('default_workflow', 'upload');
    }

    public function update_resource_action()
    {
        $this->resources = OCModel::getOCRessources();
        foreach ($this->resources as $resource) {
            if (Request::get('action') == 'add') {
                if (($candidate_ca = Request::get($resource['resource_id'])) && $candidate_wf = Request::get('workflow')) {
                    $success = OCModel::setCAforResource($resource['resource_id'], $candidate_ca, $candidate_wf);
                }
            }
        }
        if ($success) {
            PageLayout::postSuccess($this->_('Capture Agents wurden zugewiesen.'));
        }

        if (Request::option('override_other_workflows', 'off') == 'on') {
            OCCourseModel::removeWorkflowsWithoutCustomCourseID('default_workflow', 'upload');
            PageLayout::postSuccess($this->_('Alle von Nutzern angepassten Workfloweinstellungen wurden entfernt.'));
        }

        // set default workflow, this needs to be done after the removal
        $workflow = Request::get('oc_course_uploadworkflow');
        OCCourseModel::setWorkflowWithCustomCourseID('default_workflow', $workflow, 'upload');

        PageLayout::postSuccess($this->_('Standardworkflow eingestellt.'));

        $this->redirect('admin/resources');
    }

    public function remove_ca_action($resource_id, $capture_agent)
    {
        OCModel::removeCAforResource($resource_id, $capture_agent);
        $this->redirect('admin/resources');
    }

    // client status
    public function client_action()
    {
        $caa_client   = CaptureAgentAdminClient::getInstance();
        $this->agents = $caa_client->getCaptureAgents();
    }

    public function add_server_action()
    {
        $config = new OCConfig();
        $config->store();

        $this->redirect('admin/config');
    }
}
