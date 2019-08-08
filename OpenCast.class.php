<?php
/*
 * OpenCast.class.php - A course plugin for Stud.IP which includes an opencast player
 */

include('bootstrap.php');

//Rest.IP
NotificationCenter::addObserver('OpenCast', 'getAPIDataForCourseRecordings', 'restip.courses.get');
NotificationCenter::addObserver('OpenCast', 'getAPIDataForCourseRecordings', 'restip.courses-course_id.get');
NotificationCenter::addObserver('OpenCast', 'getAPIDataForCourseRecordings', 'restip.courses-semester-semester_id.get');

class OpenCast extends StudipPlugin implements SystemPlugin, StandardPlugin
{
    const GETTEXT_DOMAIN = 'opencast';

    /**
     * Initialize a new instance of the plugin.
     */
    function __construct()
    {
        parent::__construct();

        bindtextdomain(static::GETTEXT_DOMAIN, $this->getPluginPath() . '/locale');
        bind_textdomain_codeset(static::GETTEXT_DOMAIN, 'ISO-8859-1');

        global $SessSemName, $perm;
        $GLOBALS['ocplugin_path'] = $this->getPluginURL();

        if ($perm->have_perm('root')) {

            //check if we already have an connection to an opencast matterhorn
            //.. now the subnavi
            $main = new Navigation($this->_("Opencast Administration"));
            // TODO think about an index page.. for the moment the config page is in charge..
            $main->setURL(PluginEngine::getURL('opencast/admin/config'));

            $config = new Navigation($this->_('Opencast Einstellungen'));
            $config->setURL(PluginEngine::getURL('opencast/admin/config'));
            $main->addSubNavigation('oc-config', $config);

            Navigation::addItem('/start/opencast', $main);
            Navigation::addItem('/admin/config/oc-config', $config);

            if (OCModel::getConfigurationstate()) {
                $resources = new Navigation($this->_('Opencast Ressourcen'));
                $resources->setURL(PluginEngine::getURL('opencast/admin/resources'));
                $main->addSubNavigation('oc-resources', $resources);
                Navigation::addItem('/admin/config/oc-resources', $resources);

                if ($perm->have_perm('root')) {
                    $mediastatus = new Navigation($this->_('Opencast Medienstatus'));
                    $mediastatus->setURL(PluginEngine::getURL('opencast/admin/mediastatus'));
                    $main->addSubNavigation('oc-mediastatus', $mediastatus);
                    Navigation::addItem('/admin/config/oc-mediastatus', $mediastatus);
                }
            }
        }


        if (!$GLOBALS['opencast_already_loaded']) {

            $this->addStylesheet('stylesheets/oc.less');

            PageLayout::addScript($this->getPluginUrl() . '/javascripts/application.js');

            if (class_exists('Context')) {
                $id = Context::getId();
            } else {
                $id = $GLOBALS['SessionSeminar'];
            }

            $id = Request::get('sem_id', $id);

            if ($perm->have_perm('tutor') && OCModel::getConfigurationstate()) {
                PageLayout::addScript($this->getPluginUrl() . '/javascripts/embed.js');
                PageLayout::addStylesheet($this->getpluginUrl() . '/stylesheets/embed.css');
                PageLayout::addScript($this->getpluginUrl() . '/vendor/jquery.ui.widget.js');
                PageLayout::addScript($this->getpluginUrl() . '/vendor/chosen/chosen.jquery.min.js');
                PageLayout::addStylesheet($this->getpluginUrl() . '/vendor/chosen/chosen.min.css');

            }

            if (OCModel::getConfigurationstate()) {

                StudipFormat::addStudipMarkup('opencast', '\[opencast\]', '\[\/opencast\]', 'OpenCast::markupOpencast');

            }

            NotificationCenter::addObserver($this, "NotifyUserOnNewEpisode", "NewEpisodeForCourse");
        }

        $GLOBALS['opencast_already_loaded'] = true;

        $this->add_observers();
    }

    /**
     * Plugin localization for a single string.
     * This method supports sprintf()-like execution if you pass additional
     * parameters.
     *
     * @param String $string String to translate
     * @return translated string
     */
    public function _($string)
    {
        $result = static::GETTEXT_DOMAIN === null
                ? $string
                : dcgettext(static::GETTEXT_DOMAIN, $string, LC_MESSAGES);
        if ($result === $string) {
            $result = _($string);
        }

        if (func_num_args() > 1) {
            $arguments = array_slice(func_get_args(), 1);
            $result = vsprintf($result, $arguments);
        }

        return studip_utf8decode($result);
    }

    /**
     * Plugin localization for plural strings.
     * This method supports sprintf()-like execution if you pass additional
     * parameters.
     *
     * @param String $string0 String to translate (singular)
     * @param String $string1 String to translate (plural)
     * @param mixed  $n       Quantity factor (may be an array or array-like)
     * @return translated string
     */
    public function _n($string0, $string1, $n)
    {
        if (is_array($n)) {
            $n = count($n);
        }

        $result = static::GETTEXT_DOMAIN === null
                ? $string0
                : dngettext(static::GETTEXT_DOMAIN, $string0, $string1, $n);
        if ($result === $string0 || $result === $string1) {
            $result = ngettext($string0, $string1, $n);
        }

        if (func_num_args() > 3) {
            $arguments = array_slice(func_get_args(), 3);
            $result = vsprintf($result, $arguments);
        }

        return studip_utf8decode($result);
    }

    /**
     * This method takes care of the Navigation
     *
     * @param string   course_id
     * @param string   last_visit
     */
    function getIconNavigation($course_id, $last_visit, $user_id = NULL)
    {

        $ocmodel = new OCCourseModel($course_id);
        if (!$this->isActivated($course_id) || $ocmodel->getSeriesVisibility() != 'visible') {
            return;
        }

        $this->image_path = $this->getPluginURL() . '/images/';

        if ($GLOBALS['perm']->have_studip_perm('user', $course_id)) {
            $ocgetcount = $ocmodel->getCount($last_visit);
            $text = sprintf($this->_('Es gibt %s neue Opencast Aufzeichnung(en) seit ihrem letzten Besuch.'), $ocgetcount);
        } else {
            $num_entries = 0;
            $text = $this->_('Opencast Aufzeichnungen');
        }

        $navigation = new Navigation('opencast', PluginEngine::getURL($this, array(), 'course/index/false'));
        $navigation->setBadgeNumber($num_entries);

        if ($ocgetcount > 0) {
            $navigation->setImage(Icon::create($this->getPluginURL() . '/images/opencast-red.svg', ICON::ROLE_ATTENTION, ["title" => 'Opencast']));
        } else {
            $navigation->setImage(Icon::create($this->getPluginURL() . '/images/opencast-grey.svg', ICON::ROLE_INACTIVE, ["title" => 'Opencast']));
        }

        return $navigation;
    }

    /**
     * Return a template (an instance of the Flexi_Template class)
     * to be rendered on the course summary page. Return NULL to
     * render nothing for this plugin.
     *
     * The template will automatically get a standard layout, which
     * can be configured via attributes set on the template:
     *
     *  title        title to display, defaults to plugin name
     *  icon_url     icon for this plugin (if any)
     *  admin_url    admin link for this plugin (if any)
     *  admin_title  title for admin link (default: Administration)
     *
     * @return object   template object to render or NULL
     */
    public function getInfoTemplate($course_id)
    {
        return null;
    }


    function getTabNavigation($course_id)
    {

        if (!$this->isActivated($course_id) || !OCModel::getConfigurationstate()) {
            return;
        }


        $ocmodel = new OCCourseModel($course_id);

        $main = new Navigation("Opencast");
        $main->setURL(PluginEngine::getURL('opencast/course'));

        $main->setImage(Icon::create($this->getPluginURL() . '/images/opencast-black.svg', ICON::ROLE_CLICKABLE, ["title" => 'Opencast']));
        $main->setImage(Icon::create($this->getPluginURL() . '/images/opencast-red.svg', ICON::ROLE_ATTENTION, ["title" => 'Opencast']));

        $overview = new Navigation($this->_('Aufzeichnungen'));
        $overview->setURL(PluginEngine::getURL('opencast/course/index'));

        $scheduler = new Navigation($this->_('Aufzeichnungen planen'));
        $scheduler->setURL(PluginEngine::getURL('opencast/course/scheduler'));

        $main->addSubNavigation('overview', $overview);


        if ($GLOBALS['perm']->have_studip_perm('dozent', $course_id)) {
            $series_metadata = OCModel::getConnectedSeries($course_id);
            if ($series_metadata && $series_metadata[0]['schedule'] == '1') {
                $main->addSubNavigation('scheduler', $scheduler);
            }
        }
        if($ocmodel->getSeriesVisibility() == 'visible' || $GLOBALS['perm']->have_studip_perm('dozent', $course_id)){
            return array('opencast' => $main);
        } else return array();

    }

    /**
     * return a list of ContentElement-objects, containing
     * everything new in this module
     *
     * @param  string $course_id the course-id to get the new stuff for
     * @param  int $last_visit when was the last time the user visited this module
     * @param  string $user_id the user to get the notification-objects for
     *
     * @return array an array of ContentElement-objects
     */
    function getNotificationObjects($course_id, $since, $user_id)
    {
        return false;
    }

    static function markupOpencast($markup, $matches, $contents)
    {
        $current_user_id = $GLOBALS['auth']->auth['uid'];
        $series_id       = OCModel::getSeriesForEpisode($contents);

        $course_id       = OCConfig::getCourseIdForSeries($series_id);

        $connectedSeries = OCModel::getConnectedSeries($course_id);
        $config          = OCConfig::getConfigForCourse($course_id);

        $search_client   = SearchClient::getInstance($config['config_id']);

        // TODO: get player type from config
        $embed = $search_client->getBaseURL() . "/paella/ui/embed.html?id=" . $contents;
        #$embed = $search_client->getBaseURL() . "/engage/theodul/ui/core.html?mode=embed&id=" . $contents;

        $lti_launch_data = OpencastLTI::generate_lti_launch_data(
            $current_user_id,
            $course_id,
            LTIResourceLink::generate_link('series','view complete series for course'),
            OpencastLTI::generate_tool('series', $connectedSeries[0]['series_id'])
        );

        $lti_data = json_encode(OpencastLTI::sign_lti_data(
            $lti_launch_data,
            $config['lti_consumerkey'],
            $config['lti_consumersecret']
        ));

        $lti_url = rtrim($config['service_url'], '/') . '/lti';
        $id = md5(uniqid());

        return "<script>
        OC.ltiCall('$lti_url', $lti_data, function() {
            jQuery('#$id').attr('src', '$embed');
        });
        </script>"
        . sprintf('<iframe id="%s"
                style="border:0px #FFFFFF none;"
                name="Opencast - Media Player"
                scrolling="no"
                frameborder="0"
                marginheight="0px"
                marginwidth="0px"
                width="640" height="360"
                allow="fullscreen" webkitallowfullscreen="true" mozallowfullscreen="true"
            ></iframe><br>', $id);
    }


    /**
     * getAPIDataForCourseRecordings - Event handler for modifying course data
     */
    public function getAPIDataForCourseRecordings()
    {

        $router = RestIP\Router::getInstance(null);
        $router->hook('restip.before.render', function () use ($router, $addon) {

            $result = $router->getRouteResult();


            if (key($result) === 'course') {
                if (empty($result['course']['course_id'])) {
                    return;
                }
                $pm = PluginManager::getInstance();
                $pinfo = $pm->getPluginInfo('OpenCast');
                $pid = $pinfo['id'];
                $ocmodel = new OCCourseModel($result['course']['course_id']);
                if($ocmodel->getSeriesVisibility() == 'visible') {
                    $result['course'] = OpenCast::extendCourseRoute($result['course'], $pm->isPluginActivated($pid, $result['course']['course_id']), true);
                }
            } elseif (key($result) === 'courses') {
                foreach ($result['courses'] as $index => $course) {
                    if (empty($course['course_id'])) {
                        continue;
                    }
                    $pm = PluginManager::getInstance();
                    $pinfo = $pm->getPluginInfo('OpenCast');
                    $pid = $pinfo['id'];
                    $ocmodel = new OCCourseModel($course['course_id']);
                    if($ocmodel->getSeriesVisibility() == 'visible') {
                        $result['courses'][$index] = OpenCast::extendCourseRoute($course, $pm->isPluginActivated($pid, $course['course_id']), false);
                    }
                }
            }

            $router->setRouteResult($result);
        });
    }

    public function extendCourseRoute($course, $activation = false, $additional_data = false)
    {
        if ($course['modules']['oc_matterhorn'] = $activation) {
            if ($additional_data) {
                if (!isset($course['additonal_data'])) {
                    $course['additional_data'] = array();
                }
                $course['additional_data']['oc_recordings'] = OpenCast::getRecordings($course['course_id']);
            }
        }
        return $course;

    }


    public function getRecordings($course_id)
    {

        $ocmodel = new OCCourseModel($course_id);
        $episodes = $ocmodel->getEpisodesforREST();

        return $episodes;

    }

    public function NotifyUserOnNewEpisode($x, $data){
        $ocmodel = new OCCourseModel($data['course_id']);
        if($ocmodel->getSeriesVisibility() == 'visible') {
            $course = Course::find($data['course_id']);
            $members = $course->members;

            $users = array();
            foreach($members as $member){
                $users[] = $member->user_id;
            }

            $notification =  sprintf($this->_('Neue Vorlesungsaufzeichnung  "%s" im Kurs "%s"'), $data['episode_title'], $course->name);
            PersonalNotifications::add(
                $users, PluginEngine::getLink('opencast/course/index/'. $data['episode_id']),
                $notification, $data['episode_id'],
                Assets::image_path("icons/black/file-video.svg")
            );
        }

    }


    /**
     * @inherits
     *
     * Overwrite default metadata-function to return correctly encoded strings
     * depending on Stud.IP version
     *
     * @return array correctly encoded metadata
     */
    public function getMetadata()
    {
        $metadata = parent::getMetadata();

        if (version_compare($GLOBALS['SOFTWARE_VERSION'], '4', '<=')) {
            foreach ($metadata as $key => $value) {
                if (is_string($value)) {
                    $metadata[$key] = utf8_decode($value);
                }
            }
        }

       return $metadata;
    }

    private function add_observers()
    {
        $change_capture_agent_name = new ResourceObjectAttributeChangeAction();
        $change_capture_agent_name->add_as_observer('change.capture_agent_attribute');
    }

    public static function get_plugin_id()
    {
        $statement = DBManager::get()->prepare('SELECT pluginid FROM plugins WHERE pluginclassname = ?');
        $statement->execute(['OpenCast']);
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        if ($result && count($result[0]) > 0) {
            return $result[0]['pluginid'];
        }
        return -1;
    }

    public static function activated_in_courses()
    {
        $statement = DBManager::get()->prepare("SELECT range_id FROM plugins_activated
            WHERE range_type = 'sem'
                AND pluginid = ?");
        $statement->execute([OpenCast::get_plugin_id()]);
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        $to_return = [];
        if ($result) {
            foreach ($result as $entry) {
                $to_return[] = $entry['range_id'];
            }
        }
        return $to_return;
    }
}
