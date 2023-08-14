<?php

/**
 * OpenCast.class.php - A course plugin for Stud.IP which includes an opencast player
 */
require_once __DIR__ . '/bootstrap.php';

use Opencast\LTI\OpencastLTI;
use Opencast\LTI\LtiLink;
use Opencast\Models\OCConfig;
use Opencast\Models\OCSeminarSeries;
use Opencast\Models\OCSeminarEpisodes;
use Opencast\Models\OCSeminarWorkflowConfiguration;
use Opencast\Models\OCTos;
use Opencast\Models\OCScheduledRecordings;
use Opencast\Models\OCUploadStudygroup;

use Courseware\CoursewarePlugin;

class OpenCast extends StudipPlugin implements SystemPlugin, StandardPlugin, CoursewarePlugin
{
    const GETTEXT_DOMAIN = 'opencast';

    /**
     * Initialize a new instance of the plugin.
     */
    public function __construct()
    {
        parent::__construct();

        $icon = new Icon($this->getPluginURL() . '/images/opencast-courseware.svg');

        if (\StudipVersion::newerThan('4.6')) {
            PageLayout::addStyle('.cw-blockadder-item.cw-blockadder-item-plugin-opencast-video {
                background-image:url(' . $icon->asImagePath() . ')
            }');

            PageLayout::addScript($this->getPluginUrl() . '/static/register.js');
        }

        bindtextdomain(static::GETTEXT_DOMAIN, $this->getPluginPath() . '/locale');
        bind_textdomain_codeset(static::GETTEXT_DOMAIN, 'UTF-8');

        $GLOBALS['ocplugin_path'] = $this->getPluginURL();

        if ($GLOBALS['perm']->have_perm('root')) {
            //check if we already have an connection to an opencast matterhorn
            //.. now the subnavi
            $main = new Navigation($this->_("Opencast Administration"));
            // TODO think about an index page.. for the moment the config page is in charge..
            $main->setURL(PluginEngine::getURL($this, [], 'admin/config'));

            $config = new Navigation($this->_('Opencast Einstellungen'));
            $config->setURL(PluginEngine::getURL($this, [], 'admin/config'));
            $main->addSubNavigation('oc-config', $config);

            Navigation::addItem('/start/opencast', $main);
            Navigation::addItem('/admin/config/oc-config', $config);

            if (OCModel::getConfigurationstate()) {
                $resources = new Navigation($this->_('Opencast Ressourcen'));
                $resources->setURL(PluginEngine::getURL($this, [], 'admin/resources'));
                $main->addSubNavigation('oc-resources', $resources);
                Navigation::addItem('/admin/config/oc-resources', $resources);
            }
        }

        if (!$GLOBALS['opencast_already_loaded']) {
            $this->addStylesheet('stylesheets/oc.less');
            PageLayout::addScript($this->getPluginUrl() . '/static/application.js');

            if ($GLOBALS['perm']->have_perm('tutor') && OCModel::getConfigurationstate()) {
                PageLayout::addScript($this->getPluginUrl() . '/static/embed.js');
                PageLayout::addStylesheet($this->getpluginUrl() . '/stylesheets/embed.css');
            }
            if (OCModel::getConfigurationstate()) {
                StudipFormat::addStudipMarkup('opencast', '\[opencast\]', '\[\/opencast\]', 'OpenCast::markupOpencast');
            }
            NotificationCenter::addObserver($this, 'NotifyUserOnNewEpisode', 'NewEpisodeForCourse');
            NotificationCenter::addObserver($this, 'cleanCourse', 'CourseDidDelete');
        }

        $GLOBALS['opencast_already_loaded'] = true;
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
            $result    = vsprintf($result, $arguments);
        }

        return $result;
    }

    /**
     * Plugin localization for plural strings.
     * This method supports sprintf()-like execution if you pass additional
     * parameters.
     *
     * @param String $string0 String to translate (singular)
     * @param String $string1 String to translate (plural)
     * @param mixed $n Quantity factor (may be an array or array-like)
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
            $result    = vsprintf($result, $arguments);
        }

        return $result;
    }

    /**
     * This method takes care of the Navigation
     *
     * @param string   course_id
     * @param string   last_visit
     */
    public function getIconNavigation($course_id, $last_visit, $user_id = null)
    {
        $visibility = OCSeriesModel::getVisibility($course_id);

        if (
            !$this->isActivated($course_id)
            || ($visibility['visibility'] != 'visible'
                && !OCPerm::editAllowed($course_id)
            )
        ) {
            return;
        }

        $num_entries = 0;
        $text        = $this->_('Opencast Aufzeichnungen');

        $this->image_path = $this->getPluginURL() . '/images/';
        if ($GLOBALS['perm']->have_studip_perm('user', $course_id)) {
            $num_entries = OCCourseModel::getCount($course_id, $last_visit);

            if ($num_entries) {
                $text       = sprintf(
                    $this->_('Es gibt %s neue Opencast Aufzeichnung(en) seit ihrem letzten Besuch.'),
                    $num_entries
                );
            }
        }

        $navigation = new Navigation(
            'opencast',
            PluginEngine::getURL($this, [], 'course/index/false')
        );

        $navigation->setBadgeNumber($num_entries);
        $navigation->setDescription($text);
        if ($num_entries > 0) {
            $navigation->setImage(
                Icon::create(
                    $this->getPluginURL() . '/images/opencast-red.svg',
                    Icon::ROLE_ATTENTION,
                    ['title' => $text]
                )
            );
        } else {
            $navigation->setImage(
                Icon::create(
                    $this->getPluginURL() . '/images/opencast-grey.svg',
                    Icon::ROLE_INACTIVE,
                    ['title' => $text]
                )
            );
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

    public function getTabNavigation($course_id)
    {
        if (!$this->isActivated($course_id) || !OCModel::getConfigurationstate()) {
            return;
        }

        $visibility = OCSeriesModel::getVisibility($course_id);
        $title      = 'Opencast';

        if ($visibility['visibility'] == 'invisible') {
            $title .= " (" . $this->_('versteckt') . ")";
        }
        $main    = new Navigation($title);

        $main->setURL(PluginEngine::getURL($this, [], 'course/index'));

        $overview = new Navigation($this->_('Aufzeichnungen'));
        $overview->setURL(PluginEngine::getURL($this, [], 'course/index'));
        $main->addSubNavigation('overview', $overview);

        $course = Seminar::getInstance($course_id);

        if (
            OCPerm::editAllowed($course_id)
            && !$course->isStudygroup()
        ) {

            $scheduler = new Navigation($this->_('Aufzeichnungen planen'));
            $scheduler->setURL(PluginEngine::getURL($this, [], 'course/scheduler'));
            $series_metadata = OCSeminarSeries::findBySeminar_id($course_id);

            if ($series_metadata) {
                $main->addSubNavigation('scheduler', $scheduler);
            }
        }

        $studyGroupId = OCUploadStudygroup::findOneBySQL('course_id = ? AND active = TRUE', [$course_id])['studygroup_id'];
        $linkedCourseId = OCUploadStudygroup::findOneBySQL('studygroup_id = ? AND active = TRUE', [$course_id])['course_id'];

        // check, if user is in course
        if (!empty($studyGroupId) && OCPerm::editAllowed($studyGroupId)) {
            foreach ($GLOBALS['SEM_CLASS'] as $id => $sem_class) {
                if ($sem_class['name'] == 'Studiengruppen') {
                    $isActive = $sem_class['modules']['OpenCast']['activated'] || !$sem_class['modules']['OpenCast']['sticky'];
                    break;
                }
            }
            if ($isActive) {
                $studyGroup = new Navigation($this->_('Zur Studiengruppe'));
                $studyGroup->setURL(PluginEngine::getURL($this, ['cid' => $course_id], 'course/redirect_studygroup/' . $studyGroupId));
                $main->addSubNavigation('studygroup', $studyGroup);
            }
        }

        if (!empty($linkedCourseId)) {
            $linkedCourse = new Navigation($this->_('Zur verknüpften Veranstaltung'));
            $linkedCourse->setURL(PluginEngine::getURL($this, ['cid' => $linkedCourseId], 'course/index'));
            $main->addSubNavigation('linkedcourse', $linkedCourse);
        }

        if ($visibility['visibility'] == 'visible' || OCPerm::editAllowed($course_id)) {
            return ['opencast' => $main];
        }
        return [];
    }

    /**
     * return a list of ContentElement-objects, containing
     * everything new in this module
     *
     * @param string $course_id the course-id to get the new stuff for
     * @param int $last_visit when was the last time the user visited this module
     * @param string $user_id the user to get the notification-objects for
     *
     * @return array an array of ContentElement-objects
     */
    public function getNotificationObjects($course_id, $since, $user_id)
    {
        return false;
    }

    public static function markupOpencast($markup, $matches, $contents)
    {
        $content = '';
        $free = null;
        $config = null;
        $course_id = Context::getId() ?: null;

        if ($course_id === null) {
            $free = OCSeminarEpisodes::findBySQL('episode_id = ?  AND visible = "free"', [$contents]) ?: null;
            if ($free) {
                $tmp_series_id = OCModel::getSeriesForEpisode($contents);
                $tmp_course_id = OCConfig::getCourseIdForSeries($tmp_series_id);
                $config = OCConfig::getConfigForCourse($tmp_course_id);
            }
        } else {
            $config = OCConfig::getConfigForCourse($course_id);
        }

        if ($config !== null) {
            $paella = $config['paella'] == '0' ? false : true;

            $search_client = SearchClient::getInstance($config['config_id']);

            if ($paella) {
                $embed = $search_client->getBaseURL() . "/paella/ui/embed.html?id=" . $contents;
            } else {
                $embed = $search_client->getBaseURL() . "/engage/theodul/ui/core.html?mode=embed&id=" . $contents;
            }

            $id = md5(uniqid());
            if ($course_id !== null) {
                $connectedSeries = OCSeminarSeries::getSeries($course_id);
                $current_user_id = $GLOBALS['auth']->auth['uid'];
                $lti_link        = new LtiLink(
                    OpencastLTI::getSearchUrl($course_id),
                    $config['lti_consumerkey'],
                    $config['lti_consumersecret']
                );

                if (OCPerm::editAllowed($course_id, $current_user_id)) {
                    $role = 'Instructor';
                } elseif ($GLOBALS['perm']->have_studip_perm('autor', $course_id, $current_user_id)) {
                    $role = 'Learner';
                }

                $lti_link->setUser($current_user_id, $role, true);
                $lti_link->setCourse($course_id);
                $lti_link->setResource(
                    $connectedSeries,
                    'series',
                    'view complete series for course'
                );

                $launch_data = $lti_link->getBasicLaunchData();
                $signature   = $lti_link->getLaunchSignature($launch_data);

                $launch_data['oauth_signature'] = $signature;

                $lti_data = json_encode($launch_data);
                $lti_url  = $lti_link->getLaunchURL();

                $content .= "<script>
                OC.ltiCall('$lti_url', $lti_data, function() {
                    jQuery('#$id').attr('src', '$embed');
                });
                </script>";
            }
            $content .= '<iframe id="' . $id . '" ' .
                ($course_id !== null ?: 'src="' . $embed . '"') .
                'style="border:0px #FFFFFF none;" ' .
                'name="Opencast - Media Player" ' .
                'scrolling="no" ' .
                'frameborder="0" ' .
                'marginheight="0px" ' .
                'marginwidth="0px" ' .
                'width="640" height="360" ' .
                'allow="fullscreen" webkitallowfullscreen="true" mozallowfullscreen="true" ' .
                '></iframe><br>';
        }

        return $content;
    }

    public function NotifyUserOnNewEpisode($x, $data)
    {
        $ocmodel = new OCCourseModel($data['course_id']);
        if ($ocmodel->getSeriesVisibility() == 'visible'
            && !empty($data['episode_id'])
            && $data['visibility'] != 'invisible'
        ) {
            $course  = Course::find($data['course_id']);
            $members = $course->members;

            $users = [];
            foreach ($members as $member) {
                $users[] = $member->user_id;
            }

            $notification = sprintf($this->_('Neue Vorlesungsaufzeichnung "%s" im Kurs "%s"'), $data['episode_title'], $course->name);
            PersonalNotifications::add(
                $users,
                PluginEngine::getLink($this, [], 'course/index/' . $data['episode_id']),
                $notification,
                $data['episode_id'],
                Assets::image_path('icons/black/file-video.svg')
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

        $metadata['pluginname'] = $this->_("Opencast");
        $metadata['displayname'] = $this->_("Opencast");

        $metadata['description'] = $this->_(
            "Mit diesem Tool können Videos aus dem Vorlesungsaufzeichnungssystem "
                . "(Opencast) mit einer Stud.IP-Veranstaltung verknüpft werden. Die Aufzeichnungen werden in "
                . "einem eingebetteten Player in Stud.IP zur Verfügung gestellt. Darüberhinaus ist es mit "
                . "dieser Integration möglich die komplette Aufzeichnungsplanung für eine Veranstaltung "
                . "abzubilden. Voraussetzung hierfür sind entsprechende Einträge im Ablaufplan und eine "
                . "gebuchte Ressource mit einem Opencast-Capture-Agent. Vorhandene Medien können bei "
                . "Bedarf nachträglich über die Hochladen-Funktion zur verknüpften Serie hinzugefügt werden."
        );

        $metadata['summary'] = $this->_("Vorlesungsaufzeichnung");

        return $metadata;
    }

    public static function get_plugin_id()
    {
        $statement = DBManager::get()->prepare('SELECT pluginid
            FROM plugins WHERE pluginclassname = ?');
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
        $result    = $statement->fetchAll(PDO::FETCH_ASSOC);
        $to_return = [];
        if ($result) {
            foreach ($result as $entry) {
                $to_return[] = $entry['range_id'];
            }
        }
        return $to_return;
    }

    /**
     * Return the name of this plugin.
     */
    public function getPluginName()
    {
        return 'Opencast';
    }

    /**
     * Returns whether the plugin may be activated in a certain context.
     *
     * @param Range $context
     * @return bool
     */
    public function isActivatableForContext(Range $context)
    {
        if (
            !Config::get()->OPENCAST_ALLOW_STUDYGROUP_CONF &&
            !$GLOBALS['perm']->have_perm('root') &&
            $context->getRangeType() === 'course' &&
            $context->getSemClass()['studygroup_mode']
        ) {
            return false;
        }
        if ($context->getRangeType() === 'institute') {
            return false;
        }
        return true;
    }

    public function cleanCourse($event, $course)
    {
        $course_id = $course->getId();
        OCScheduledRecordings::deleteBySQL('seminar_id = ?', [$course_id]);
        OCSeminarEpisodes::deleteBySQL('seminar_id = ?', [$course_id]);
        OCSeminarSeries::deleteBySQL('seminar_id = ?', [$course_id]);
        OCSeminarWorkflowConfiguration::deleteBySQL('seminar_id = ?', [$course_id]);
        OCTos::deleteBySQL('seminar_id = ?', [$course_id]);

        if ($course_link = OCUploadStudygroup::findOneBySQL('course_id = ?', [$course_id])) {
            $studygroup_id = $course_link['studygroup_id'];
            $course_link->delete();
            Course::find($studygroup_id)->delete();
        } else if ($studygroup_link = OCUploadStudygroup::findOneBySQL('studygroup_id = ?', [$course_id])) {
            $studygroup_link->delete();
        }
    }

    /**
     * Implement this method to register more block types.
     *
     * You get the current list of block types and must return an updated list
     * containing your own block types.
     *
     * @param array $otherBlockTypes the current list of block types
     *
     * @return array the updated list of block types
     */
    public function registerBlockTypes(array $otherBlockTypes): array
    {
        $otherBlockTypes[] = OpencastBlock::class;

        return $otherBlockTypes;
    }

    /**
     * Implement this method to register more container types.
     *
     * You get the current list of container types and must return an updated list
     * containing your own container types.
     *
     * @param array $otherContainerTypes the current list of container types
     *
     * @return array the updated list of container types
     */
    public function registerContainerTypes(array $otherContainerTypes): array
    {
        return $otherContainerTypes;
    }
}
