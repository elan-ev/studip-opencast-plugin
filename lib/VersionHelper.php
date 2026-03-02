<?php

namespace Opencast;

class VersionHelper
{
    public static function get()
    {
        static $vh;

        if (!$vh) {
            if (\StudipVersion::newerThan('5.5')) {
                $vh = new VersionHelper6();
            } else {
                $vh = new VersionHelper5();
            }
        }

        return $vh;
    }

    public static function autoloadVendor()
    {
        static $loaded = false;

        if ($loaded) {
            return;
        }

        $autoload_path = null;
        if (class_exists('\StudipVersion')) {
            $autoload_path = self::get()->getComposerAutoloadPath();
        }

        if (!$autoload_path) {
            $autoload_path = __DIR__ . '/../vendor/autoload.php';
        }

        if (is_readable($autoload_path)) {
            require_once $autoload_path;
            $loaded = true;
        }
    }

    public static function createResponse(): \Psr\Http\Message\ResponseInterface
    {
        if (class_exists('\Slim\Psr7\Response')) {
            return new \Slim\Psr7\Response();
        }

        if (class_exists('\Nyholm\Psr7\Response')) {
            return new \Nyholm\Psr7\Response();
        }

        if (class_exists('\GuzzleHttp\Psr7\Response')) {
            return new \GuzzleHttp\Psr7\Response();
        }

        throw new \RuntimeException('No PSR-7 Response implementation available.');
    }
}

interface VersionHelperInterface
{
    /**
     * Since there is no contents area in 4.6 and below, we need to correctly place the main nav item
     *
     * @param \Navigation $navigation
     *
     * @return void
     */
    function addMainNavigation(\Navigation $navigation);

    /**
     * Since there is no contents area in 4.6 and below, the activation pathes for the navigation differ as well
     *
     * @return void
     */
    function activateContentNavigation();

    /**
     * The way plugin activations in courses are stored change in 5.0. Get correct SQL to get plugin activations
     *
     * @return void
     */
    function getPluginActivatedSQL();

    /**
     * Returns the path to a version specific stylesheet, if any
     *
     * @param \StudipPlugin $plugin
     *
     * @return string null, if no version specific stylesheet is needed
     */
    function getVersionSpecificStylesheet();

    /**
     * Register the correct courseware block, for 5.0 and up use the integrated courseware, for lower version use the courseware plugin
     *
     * @return void
     */
    function registerCoursewareBlock(\StudipPlugin $plugin);

    /**
     * Returns the path to the composer autoload file for the current Stud.IP version.
     *
     * @return string|null
     */
    function getComposerAutoloadPath();
}

class VersionHelper5 implements VersionHelperInterface
{
    function addMainNavigation(\Navigation $navigation)
    {
        global $user;

        if (\Navigation::hasItem('/contents') && $user->perms != 'admin') {
            \Navigation::addItem('/contents/opencast', $navigation);
        }
    }

    function activateContentNavigation()
    {
        if (\Navigation::hasItem('/contents')) {
            \Navigation::activateItem('/contents/opencast');
        }
    }

    function getPluginActivatedSQL()
    {
        return ' JOIN tools_activated ON (
            tools_activated.range_id = seminar_id
            AND tools_activated.plugin_id = :plugin_id
        ) ';
    }

    function getVersionSpecificStylesheet()
    {
        return null;
    }

    function registerCoursewareBlock(\StudipPlugin $plugin)
    {
        $icon = new \Icon($plugin->assetsUrl . '/images/opencast-courseware.svg');

        \PageLayout::addStyle('.cw-blockadder-item.cw-blockadder-item-plugin-opencast-video {
            background-image:url(' . $icon->asImagePath() . ') !important;
        }');

        \PageLayout::addScript($plugin->getPluginUrl() . '/static_cw/register-vue2.umd.js');

        require_once __DIR__ . '/Versions/5.x/OpencastBlockV3.php';
    }

    function getComposerAutoloadPath()
    {
        return __DIR__ . '/../vendor-studip5/autoload.php';
    }
}

class VersionHelper6 implements VersionHelperInterface
{
    function addMainNavigation(\Navigation $navigation)
    {
        global $user;

        if (\Navigation::hasItem('/contents') && $user->perms != 'admin') {
            \Navigation::addItem('/contents/opencast', $navigation);
        }
    }

    function activateContentNavigation()
    {
        if (\Navigation::hasItem('/contents')) {
            \Navigation::activateItem('/contents/opencast');
        }
    }

    function getPluginActivatedSQL()
    {
        return ' JOIN tools_activated ON (
            tools_activated.range_id = seminar_id
            AND tools_activated.plugin_id = :plugin_id
        ) ';
    }

    function getVersionSpecificStylesheet()
    {
        return null;
    }

    function registerCoursewareBlock(\StudipPlugin $plugin)
    {
        $icon = new \Icon($plugin->assetsUrl . '/images/opencast-courseware.svg');

        \PageLayout::addStyle('.cw-blockadder-item.cw-blockadder-item-plugin-opencast-video {
            background-image:url(' . $icon->asImagePath() . ') !important;
        }');

        \PageLayout::addScript($plugin->getPluginUrl() . '/static_cw/register-vue3.umd.js');

        require_once __DIR__ . '/BlockTypes/OpencastBlockV3.php';
    }

    function getComposerAutoloadPath()
    {
        return __DIR__ . '/../vendor-studip6/autoload.php';
    }
}
