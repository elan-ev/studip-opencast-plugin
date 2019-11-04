<?php
/*
 * admin.php - admin plugin controller
 */

use Opencast\Models\OCConfig;
use Opencast\Models\OCEndpoints;
use Opencast\Models\OCSeminarSeries;
use Opencast\LTI\OpencastLTI;

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

        $this->config = OCConfig::getBaseServerConf();
        $this->global_config = Configuration::instance(Opencast\Constants::$GLOBAL_CONFIG_ID);
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
            var_dump($apiseries->putJSON('/'. $series_id .'/acl', [
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

        $api         = ApiWorkflowsClient::getInstance(1);
        $api_events  = ApiEventsClient::getInstance(1);

        $fd = fopen($filename = __DIR__ . '/episodes.txt', 'r');

        while ($episode_id = fgets($fd)) {
            $episode_id = str_replace("\n", '', $episode_id);
            echo 'Verarbeite ' . $episode_id ."...\n";
            $api_events->postJson('/'. $episode_id . '/acl/read', ['role' => 'ROLE_ANONYMOUS']);
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
        if(!$data){
            $data = $oc->getJSON('/sysinfo/bundles/version?prefix=opencast');
        }

        // always use the first found version information
        if (is_array($data->versions)) {
            $data = $data->versions[0];
        }

        return (int)substr($data->version, 0, 1);
    }

    function update_action()
    {
        // invalidate series-cache when editing configuration
        StudipCacheFactory::getCache()->expire('oc_allseries');

        foreach (Request::getArray('config') as $config_id => $config) {
            //set precise settings if any
            $precise_config = $config['precise'];

            foreach($precise_config as $name => $value){
                if (Configuration::instance()[$name] != $value){
                    Configuration::instance($config_id)->set($name, $value, Configuration::instance()->get_description_for($name));
                } else {
                    Configuration::instance($config_id)->remove($name);
                }
            }

            // if no data is given (i.e.: The selected config shall be deleted!),
            // remove config data properly
            if (!$config['url']) {
                OCConfig::clearConfigAndAssociatedEndpoints($config_id);
                continue;
            }

            $service_url =  parse_url($config['url']);

            // check the selected url for validity
            if (!array_key_exists('scheme', $service_url)) {
                $this->flash['messages'] = [
                    'error' => sprintf(
                        $this->_('Ungültiges URL-Schema: "%s"'),
                        $config['url']
                    )
                ];
                OCConfig::clearConfigAndAssociatedEndpoints($config_id);
            } else {
                $service_host =
                    $service_url['scheme'] .'://' .
                    $service_url['host'] .
                    (isset($service_url['port']) ? ':' . $service_url['port'] : '');

                try {
                    $version = $this->getOCBaseVersion($service_host, $config['user'], $config['password']);

                    OCConfig::clearConfigAndAssociatedEndpoints($config_id);
                    OCConfig::setConfig($config_id, $service_host, $config['user'], $config['password'], $version);

                    // check, if the same url has been provided for multiple oc-instances
                    foreach (Request::getArray('config') as $zw_id => $zw_conf) {
                        if ($zw_id != $config_id && $zw_conf['url'] == $config['url']) {
                            $this->flash['messages'] = array(
                                'error' => sprintf(
                                    $this->_('Sie haben mehr als einmal dieselbe URL für eine Opencast Installation angegeben.
                                        Dies ist jedoch nicht gestattet. Bitte korrigieren Sie Ihre Eingaben. URL: "%s"'),
                                     $config['url']
                                )
                            );

                            continue 2;
                        }
                    }


                    OCEndpoints::setEndpoint($config_id, $service_host .'/services', 'services');

                    $services_client = new ServicesClient($config_id);

                    $comp = null;
                    $comp = $services_client->getRESTComponents();
                } catch (AccessDeniedException $e) {
                    OCEndpoints::removeEndpoint($config_id, 'services');

                    $this->flash['messages'] = array(
                        'error' => sprintf(
                            $this->_('Fehlerhafte Zugangsdaten für die Opencast Installation mit der URL "%s". Überprüfen Sie bitte die eingebenen Daten.'),
                            $service_host
                        )
                    );

                    $this->redirect('admin/config');
                    return;
                }

                if ($comp) {
                    $services = OCModel::retrieveRESTservices($comp, $service_url['scheme']);

                    if (empty($services)) {
                        OCEndpoints::removeEndpoint($config_id, 'services');

                        $this->flash['messages'] = array(
                            'error' => sprintf(
                                $this->_('Es wurden keine Endpoints für die Opencast Installation mit der URL "%s" gefunden. '
                                    . 'Überprüfen Sie bitte die eingebenen Daten, achten Sie dabei auch auf http vs https und '
                                    . 'ob ihre Opencast-Installation https unterstützt.'),
                                $service_host
                            )
                        );
                    } else {

                        foreach($services as $service_url => $service_type) {
                            if (in_array(strtolower($service_type), Opencast\Constants::$SERVICES) !== false) {
                                OCEndpoints::setEndpoint($config_id, $service_url, $service_type);
                            } else {
                                unset($services[$service_url]);
                            }
                        }

                        $success_message[] = sprintf(
                            $this->_('Die Opencast Installation "%s" wurde erfolgreich konfiguriert.'),
                            $service_host
                        );

                        $this->flash['messages'] = array('success' => implode('<br>', $success_message));
                    }
                } else {
                    OCEndpoints::removeEndpoint($config_id, 'services');
                    $this->flash['messages'] = array(
                        'error' => sprintf(
                            $this->_('Es wurden keine Endpoints für die Opencast Installation mit der URL "%s" gefunden. Überprüfen Sie bitte die eingebenen Daten.'),
                            $service_host
                        )
                    );
                }
            }
        }

        // after updating the configuration, clear the cached series data
        OCSeriesModel::clearCachedSeriesData();
        #OpencastLTI::generate_complete_acl_mapping();

        $this->redirect('admin/config');
    }


    function endpoints_action()
    {
        PageLayout::setTitle($this->_("Opencast Endpoint Verwaltung"));
        // Navigation::activateItem('/admin/config/oc-endpoints');

        $this->configs = OCConfig::getBaseServerConf();
        $this->endpoints = OCEndpoints::getEndpoints();
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
        if (empty($this->resources)) {
            $this->flash['messages'] = [
                'info' => $this->_('Es wurden keine passenden Ressourcen gefunden.')
            ];
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
                    if ($agent->name ==  $assigned_agents['capture_agent']) {
                        unset($agents[$key]);
                        $existing_agent = true;
                    }
                }

                if (!$existing_agent){
                    OCModel::removeCAforResource($resource['resource_id'], $assigned_agents['capture_agent']);
                    $this->flash['messages'] = array('info' => sprintf($this->_("Der Capture Agent %s existiert nicht mehr und wurde entfernt."),$assigned_agents['capture_agent'] ));
                }
            }
        }

        $this->available_agents = $agents;
        $this->definitions = $workflow_client->getDefinitions();

        $this->assigned_cas = OCModel::getAssignedCAS();

        $this->workflows = array_filter(
            $workflow_client->getTaggedWorkflowDefinitions(),
            function ($element) {
                return (in_array('schedule', $element['tags']) !== false
                    || in_array('schedule-ng', $element['tags']) !== false)
                    ? $element
                    : false;
            }
        );

        $this->current_workflow = OCCourseModel::getWorkflowWithCustomCourseID('default_workflow','upload');
    }


    function update_resource_action()
    {
        $this->resources = OCModel::getOCRessources();
        foreach ($this->resources as $resource) {
            if (Request::get('action') == 'add') {
                if (($candidate_ca = Request::get($resource['resource_id'])) && $candidate_wf = Request::get('workflow')) {
                    $success = OCModel::setCAforResource($resource['resource_id'], $candidate_ca, $candidate_wf);
                }
            }
        }

        $messages = [];

        if ($success) {
            $messages['success'][] = $this->_("Capture Agents wurden zugewiesen.");
        }

        $workflow = Request::get('oc_course_uploadworkflow');
        OCCourseModel::setWorkflowWithCustomCourseID('default_workflow', $workflow, 'upload');

        $messages['success'][] = $this->_("Standardworkflow eingestellt.");
        $override = Request::option('override_other_workflows','off'); // on / off
        if($override == 'on'){
            $override_success = OCCourseModel::removeWorkflowsWithoutCustomCourseID('default_workflow','upload');
            if($override_success){
                $messages['success'][] = $this->_("Andere Workflow Einstellungen wurden entfernt.");
            }else{
                $messages['error'][] = $this->_('Andere Workflows konnten nicht entfernt werden.');
            }
        }

        foreach ($messages as $type=>$collection){
            $messages[$type] = implode(' ',$collection);
        }
        $this->flash['messages'] = $messages;

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

    function refresh_cache_action($ticket)
    {
        if (check_ticket($ticket) && $GLOBALS['perm']->have_perm('root')) {
            // expire Stud.IP Cache
            StudipCacheFactory::getCache()->expire('oc_allseries');

            $this->flash['messages'] = [
                'success' => $this->_("Der Zwischenspeicher wurde geleert.")
            ];
        }

        $this->redirect('admin/config/');
    }

    function refresh_episodes_action($ticket)
    {
        if (check_ticket($ticket) && $GLOBALS['perm']->have_perm('root')) {
            // refresh database entries
            $stmt = DBManager::get()->prepare("SELECT
                DISTINCT ocs.seminar_id, ocs.series_id
                FROM oc_seminar_series AS ocs
                WHERE 1");
            $stmt->execute(array());
            $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (is_array($courses)) {
                foreach ($courses as $course) {

                    $ocmodel = new OCCourseModel($course['seminar_id']);
                    $ocmodel->getEpisodes(true);
                    unset($ocmodel);
                }

                $this->flash['messages'] = [
                    'success' => $this->_("Die Episodenliste aller Series  wurde aktualisiert.")
                ];
            }
        }

        $this->redirect('admin/config/');
    }

    function mediastatus_action()
    {
        PageLayout::setTitle($this->_("Opencast Medienstatus"));
        Navigation::activateItem('/admin/config/oc-mediastatus');

        // OPENCAST TMP-DIRECTORY CONTENT
        $undeleted_jobs = OCJobManager::existent_jobs();
        $this->upload_jobs = [
            'successful'=>[],
            'unfinished'=>[]
        ];

        foreach ($undeleted_jobs as $undeleted_job_id){
            $job = new OCJob($undeleted_job_id);
            $this->upload_jobs[($job->both_uploads_succeeded() ? 'successful' : 'unfinished')][] = $job;
        }

        $this->memory_space = OCJobManager::save_dir_size();
    }

    function precise_update_action()
    {
        foreach (Request::getArray('precise_config') as $database_id => $config) {
            foreach ($config as $name => $value){
                Configuration::instance($database_id)[$name] = $value;
            }
        }

        $config = reset(OCConfig::findByConfig_id(1));

        if ($config) {
            $config->tos = Request::i18n('tos');
            $config->store();
        }

        $this->redirect('admin/config/');
    }

    function precise_add_action(){

        $this->redirect('admin/config/');
    }

    function precise_remove_action(){

        $this->redirect('admin/config/');
    }
}
