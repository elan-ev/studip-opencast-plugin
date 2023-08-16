<?php

/**
 * OpenCast.class.php - A course plugin for Stud.IP which includes an opencast player
 */
require_once __DIR__ . '/bootstrap.php';

use Opencast\Models\Helpers;
use Opencast\Models\SeminarSeries;

use Opencast\AppFactory;
use Opencast\RouteMap;
use Opencast\VersionHelper;
use Opencast\Providers\Perm;

use Courseware\CoursewarePlugin;

class OpenCast extends StudipPlugin implements SystemPlugin, StandardPlugin, CoursewarePlugin
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
     * @param string   course_id
     * @param string   last_visit
     */
    public function getIconNavigation($course_id, $last_visit, $user_id = null)
    {
        require_once __DIR__ . '/vendor/autoload.php';

        $navigation = new Navigation(
            $this->_('Opencast Videos'),
            PluginEngine::getURL($this, [], 'course/#/course/videos')
        );
        $navigation->setImage(Icon::create('video2'));

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

        $title      = 'Opencast Videos';
        if (SeminarSeries::getVisibilityForCourse($course_id) == 'invisible') {
            $title .= " (" . $this->_('versteckt') . ")";
        }

        $main = new Navigation(
            $this->_($title),
            PluginEngine::getURL($this, [], 'course#/course/videos')
        );
        $main->setImage(Icon::create('video2'));

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
                . "mit dem Online-Tool Opencast Studio auch direkt selbst erstellt werden. "
                . "Auch auch komplette Wiedergabelisten können eingebunden werden. "
        );

        if (Config::get()->OPENCAST_ALLOW_SCHEDULER) {
            $description .= $this->_("Darüberhinaus ist es mit "
                . "dieser Integration möglich die komplette Aufzeichnungsplanung für eine Veranstaltung "
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
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function perform($unconsumed_path)
    {
        require_once __DIR__ . '/vendor/autoload.php';

        if (substr($unconsumed_path, 0, 3) == 'api') {
            $appFactory = new AppFactory();
            $app = $appFactory->makeApp($this);
            $app->group('/opencast/api', new RouteMap($app));
            $app->run();
        } else {
            $this->addStylesheet("assets/css/opencast.scss");
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
}
