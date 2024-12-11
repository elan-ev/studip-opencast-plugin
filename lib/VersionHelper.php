<?php

namespace Opencast;

class VersionHelper
{
    public static function get()
    {
        static $vh;

        if (\StudipVersion::newerThan('4.6')) {
            if (!$vh) {
                $vh = new VersionHelper50();
            }
        } else {
            $vh = new VersionHelper46();
        }

        return $vh;
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
}

class VersionHelper46 implements VersionHelperInterface
{
    function addMainNavigation(\Navigation $navigation)
    {
        \Navigation::addItem('/opencast', $navigation);
    }

    function activateContentNavigation()
    {
        \Navigation::activateItem('/opencast');
    }

    function getPluginActivatedSQL()
    {
        return ' JOIN plugins_activated ON (
            plugins_activated.range_id = seminar_id
            AND plugins_activated.pluginid = :plugin_id
            AND plugins_activated.state = 1
        ) ';
    }

    function getVersionSpecificStylesheet()
    {
        return 'assets/css/studip46.scss';
    }

    function registerCoursewareBlock(\StudipPlugin $plugin)
    {
        // load compatibility JS-script
        \PageLayout::addScript($plugin->getPluginUrl() . '/assets/javascript/oc46.js');

        // load classes need for the old Courseware OC block
        require_once(__DIR__ . '/Versions/4.6/OCConfig.php');
        require_once(__DIR__ . '/Versions/4.6/OpencastLTI.php');
        require_once(__DIR__ . '/Versions/4.6/LtiLink.php');
    }
}

class VersionHelper50 implements VersionHelperInterface
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

        \PageLayout::addScript($plugin->getPluginUrl() . '/static_cw/register.js');
    }
}