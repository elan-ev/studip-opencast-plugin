<?php

/**
 * OpencastV3.class.php - A course plugin for Stud.IP which includes an opencast player
 */

require_once __DIR__ . '/bootstrap.php';

use Opencast\Models\Helpers;
use Opencast\Models\SeminarSeries;
use Opencast\Models\Videos;
use Opencast\Models\WidgetHelper;

use Opencast\AppFactory;
use Opencast\RouteMap;
use Opencast\VersionHelper;
use Opencast\Providers\Perm;

use Courseware\CoursewarePlugin;

class OpencastV3 extends StudipPlugin implements SystemPlugin, StandardPlugin, CoursewarePlugin, PortalPlugin
{
    const GETTEXT_DOMAIN = 'opencast';

    public $assetsUrl;

    /**
     * Initialize a new instance of the plugin.
     */
    public function __construct()
    {
        parent::__construct();

        bindtextdomain(static::GETTEXT_DOMAIN, $this->getPluginPath() . '/locale');
        bind_textdomain_codeset(static::GETTEXT_DOMAIN, 'UTF-8');
        $this->assetsUrl = rtrim($this->getPluginURL(), '/') . '/assets';

        if ($GLOBALS['perm']->have_perm('root')) {
            $config = new Navigation($this->_('Opencast Einstellungen'), PluginEngine::getURL($this, [], 'admin#/admin'));
            if (Navigation::hasItem('/admin/config') && !Navigation::hasItem('/admin/config/oc-config')) {
                Navigation::addItem('/admin/config/oc-config', $config);
            }
        }

        if ($GLOBALS['perm']->have_perm('autor') && Helpers::getConfigurationstate()) {
            /*
            if (!\Config::get()->OPENCAST_MEDIA_ROLES || (
                \Config::get()->OPENCAST_MEDIA_ROLES && (
                    Perm::hasRole('Medienadmin')
                    || Perm::hasRole('Medientutor')
                    || $GLOBALS['perm']->have_perm('dozent')
                )
            )) {
            */
                // only show main navigation, if media roles are disabled or user has a media role
                $videos = new Navigation($this->_('Opencast Videos'));
                $videos->setDescription($this->_('Opencast Aufzeichnungen'));
                $videos->setImage(Icon::create($this->assetsUrl . '/images/opencast-courseware.svg'));
                $videos->setURL(PluginEngine::getURL($this, [], 'contents/index#/contents/videos'));

                // use correct navigation for Stud.IP Versions below 5
                VersionHelper::get()->addMainNavigation($videos);
            // }
        }

        $this->addStylesheet("assets/css/courseware.scss");
        $this->addStylesheet("assets/css/opencast.scss");
        VersionHelper::get()->registerCoursewareBlock($this);
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
     * @param string    $course_id
     * @param int       $last_visit
     * @param string    $user_id
     */
    public function getIconNavigation($course_id, $last_visit, $user_id = null)
    {
        require_once __DIR__ . '/vendor/autoload.php';

        $navigation = new Navigation(
            $this->_('Opencast Videos'),
            PluginEngine::getURL($this, [], 'course/#/course/videos')
        );
        $navigation->setImage(Icon::create('opencast'));

        // Get number of new videos since last visit
        $new_videos = Videos::getNumberOfNewCourseVideos($course_id, $last_visit, $user_id);

        if ($new_videos > 0) {
            if ($new_videos == 1) {
                $text = $this->_('neues Video');
            } else {
                $text = $this->_('neue Videos');
            }
            $navigation->setImage(Icon::create('opencast', Icon::ROLE_ATTENTION, [
                'title' => $new_videos . ' ' . $text,
            ]));
            $navigation->setBadgeNumber($new_videos);
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
     *  title        title to display, defaulAppFactoryts to plugin name
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
        require_once __DIR__ . '/vendor/autoload.php';

        if (!$this->isActivated($course_id) || !Helpers::getConfigurationstate()) {
            return;
        }

        $title = 'Opencast Videos';

        $main = new Navigation(
            $this->_($title),
            PluginEngine::getURL($this, [], 'course#/course/videos')
        );
        $main->setImage(Icon::create('opencast'));

        // We need subnavs in order for responsive view to work properly.
        $main->addSubNavigation('videos', new Navigation(
            $this->_('Videos'),
            PluginEngine::getURL($this, ['target_view' => 'videos'], 'course#/course/videos')
        ));

        if ($GLOBALS['perm']->have_studip_perm('tutor', $course_id) &&
            Config::get()->OPENCAST_ALLOW_SCHEDULER &&
            Helpers::checkCourseDefaultPlaylist($course_id)) {
            $main->addSubNavigation('schedule', new Navigation(
                $this->_('Aufzeichnungen planen'),
                PluginEngine::getURL($this, ['target_view' => 'schedule'], 'course#/course/schedule')
            ));
        }

        return ['opencast' => $main];
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

        $metadata['pluginname'] = $this->_("Opencast Videos");
        $metadata['displayname'] = $this->_("Opencast Videos");

        $description =  $this->_(
            "Mit diesem Tool können Videos aus dem Vorlesungsaufzeichnungssystem "
                . "(Opencast) in einer Stud.IP-Veranstaltung angezeigt werden. Die Videos können aus dem eigenen "
                . "Videobereich der Veranstaltung hinzugefügt, direkt in der Veranstaltung hochgeladen oder "
                . "mit dem Online-Tool Opencast Studio direkt selbst erstellt werden. "
                . "Auch komplette Wiedergabelisten können eingebunden werden. "
        );

        if (Config::get()->OPENCAST_ALLOW_SCHEDULER) {
            $description .= $this->_("Darüber hinaus ist es mit "
                . "dieser Integration möglich, die komplette Aufzeichnungsplanung für eine Veranstaltung "
                . "abzubilden. Voraussetzung hierfür sind entsprechende Einträge im Ablaufplan und eine "
                . "gebuchte Ressource mit einem Opencast-Capture-Agent."
            );
        }

        $metadata['description'] = $description;
        $metadata['summary'] = $this->_("Videos & Vorlesungsaufzeichnung");

        return $metadata;
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
        // This plugin does not work in institutes
        if (!$context instanceof Course) {
            return false;
        }

        if ($context->getSemType()->getClass()['studygroup_mode'])
        {
            if (\Config::get()->OPENCAST_ALLOW_STUDYGROUP_CONF) {
                return true;
            }

            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function perform($unconsumed_path)
    {
        require_once __DIR__ . '/vendor/autoload.php';

        if (substr($unconsumed_path, 0, 3) == 'api') {
            // make sure, slim knows if we are running https, see https://github.com/elan-ev/studip-opencast-plugin/issues/816
            if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
                $_SERVER['HTTPS'] = 'on';
                $_SERVER['SERVER_PORT'] = 443;
            }

            $appFactory = new AppFactory();
            $app = $appFactory->makeApp($this);
            $app->setBasePath(rtrim(PluginEngine::getLink($this, [], null, true), '/'));
            $app->group('/api', RouteMap::class);

            $app->run();
        } else {
            if (!empty($GLOBALS['ABSOLUTE_URI_STUDIP'])) {
                URLHelper::setBaseURL($GLOBALS['ABSOLUTE_URI_STUDIP']);
            }

            $css = VersionHelper::get()->getVersionSpecificStylesheet();

            if ($css) {
                $this->addStylesheet($css);
            }

            $trails_root = $this->getPluginPath() . '/app';
            $dispatcher  = new Trails_Dispatcher(
                $trails_root,
                rtrim(PluginEngine::getURL($this, null, ''), '/'),
                'index'
            );

            $dispatcher->current_plugin = $this;
            $dispatcher->dispatch($unconsumed_path);
        }
    }

    public function getAssetsUrl()
    {
        return $this->assetsUrl;
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
        $otherBlockTypes[] = OpencastBlockV3::class;

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

    /**
     * get the plugin manifest from PluginManager getPluginManifest method
     *
     * @return Array $metadata the manifest metadata of this plugin
     */
    public static function getPluginManifestInfo()
    {
        $plugin_manager = \PluginManager::getInstance();
        $this_plugin = $plugin_manager->getPluginInfo(__CLASS__);
        $plugin_path = \get_config('PLUGINS_PATH') . '/' .$this_plugin['path'];
        $manifest = $plugin_manager->getPluginManifest($plugin_path);
        return $manifest;
    }

    /**
     * @inherited
     */
    public static function onEnable($plugin_id)
    {
        // add nobody role to plugin for it to function correctly
        foreach (RolePersistence::getAllRoles() as $role) {
            if ($role->systemtype && $role->rolename == 'Nobody') {
                RolePersistence::assignPluginRoles($plugin_id, [$role->roleid]);
                break;
            }
        }

        RolePersistence::expirePluginCache();
    }

    /**
     * Return the template for the widget.
     *
     * @return Flexi_PhpTemplate The template containing the widget contents
     */
    public function getPortalTemplate()
    {
        global $perm;
        // We need to use "nobody" rights for Upload Slides,
        // but in here we have to prevent that right,
        // in order to not to show the template in login page and so on.
        if ('nobody' === $GLOBALS['user']->id) {
            return;
        }

        $template_factory = new Flexi_TemplateFactory(__DIR__ . "/templates");
        $template = $template_factory->open("widget.php");

        $upcomings = WidgetHelper::getUpcomingLivestreams();
        $items['upcomings'] = $upcomings;
        $template->set_attribute('items', $items);

        $empty_text = $this->_('Derzeit finden keine Livestreams in den gebuchten Kursen statt.');
        if ($perm->have_perm('admin') || $perm->have_perm('root')) {
            $empty_text = $this->_('Um Leistungsprobleme zu vermeiden, ist diese Funktion für Administratoren dauerhaft deaktiviert.');
        }

        $texts = [
            'empty' => $empty_text,
            'upcomings' => $this->_('Kommende Liveevents')
        ];
        $template->set_attribute('texts', $texts);

        return $template;
    }
}
