<?php
/*
 * OpenCast.class.php - A course plugin for Stud.IP which includes an opencast player
 * Copyright (c) 2010  André Klaßen
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once 'vendor/trails/trails.php';

define('OC_UPLOAD_CHUNK_SIZE', '1000000');


class OpenCast extends StudipPlugin implements StandardPlugin
{
    /**
     * Initialize a new instance of the plugin.
     */
    function __construct()
    {
        parent::__construct();

        global $SessSemName, $perm;

        //.. now the subnavi
        $main = new Navigation("OpenCast");
        //$main = new Navigation("Veranstaltungsaufzeichnungen");
        $main->setURL(PluginEngine::getURL('opencast/course'));
        $main->setImage('../../'.$this->getPluginPath().'/images/oc-logo.png');

        $admin = new Navigation('Einstellungen');
        $admin->setURL(PluginEngine::getURL('opencast/course/config'));
        $overview = new Navigation('Aufzeichnungen');
        $overview->setURL(PluginEngine::getURL('opencast/course/index'));

        $scheduler = new Navigation('Aufzeichnungen planen');
        $scheduler->setURL(PluginEngine::getURL('opencast/course/scheduler'));


        $upload = new Navigation('Upload');
        $upload->setURL(PluginEngine::getURL('opencast/course/upload'));
        $main->addSubNavigation('overview', $overview);
        if ($perm->have_studip_perm('dozent', $SessSemName[1])) {
            // TODO: Add scheduler iff scheduling is allowed in current course
            $main->addSubNavigation('scheduler', $scheduler);
            $main->addSubNavigation('config', $admin);
            $main->addSubNavigation('upload', $upload);

        }
        // Add everything to the global Navigation
        if ($this->isActivated($_SESSION['SessionSeminar'])) {
            Navigation::addItem('/course/opencast', $main);
        }

        $style_attributes = array(
            'rel'   => 'stylesheet',
            'href'  => $GLOBALS['CANONICAL_RELATIVE_PATH_STUDIP'] . $this->getPluginPath() . '/stylesheets/oc.css');
        PageLayout::addHeadElement('link',  array_merge($style_attributes, array()));

        $script_attributes = array(
            'src'   => $GLOBALS['CANONICAL_RELATIVE_PATH_STUDIP'] . $this->getPluginPath() . '/javascripts/application.js');
        PageLayout::addHeadElement('script', $script_attributes, '');
    }

    /**
     * This method dispatches all actions.
     *
     * @param string   part of the dispatch path that was not consumed
     */
    function perform($unconsumed_path)
    {
        $trails_root = $this->getPluginPath();
        $dispatcher = new Trails_Dispatcher($trails_root, NULL, NULL);
        $dispatcher->dispatch($unconsumed_path);
    }

    /**
     * This method takes care of the Navigation
     *
     * @param string   course_id
     * @param string   last_visit
     */
    function getIconNavigation($course_id, $last_visit, $user_id)
    {
        return false;
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
        return false;
    }

    /**
     * Return a warning message to be printed before deactivation of
     * this plugin in the given context.
     *
     * @param $context   context range id
     */
    public function deactivationWarning($context = null)
    {
        return _("Das Opencastplugin wurde deaktiviert.");
    }

    /**
     * Callback function called after enabling a plugin.
     * The plugin's ID is transmitted for convenience.
     *
     * @param $pluginId string The ID of the plugin just enabled.
     */
    public static function onEnable($pluginId)
    {
        return false;
    }

    /**
     * Callback function called after disabling a plugin.
     * The plugin's ID is transmitted for convenience.
     *
     * @param $pluginId string The ID of the plugin just disabled.
     */
    public static function onDisable($pluginId)
    {
        return false;
    }

    function getTabNavigation($course_id)
    {
        return false;
    }

    /**
     * return a list of ContentElement-objects, conatinging
     * everything new in this module
     *
     * @param  string   $course_id   the course-id to get the new stuff for
     * @param  int      $last_visit  when was the last time the user visited this module
     * @param  string   $user_id     the user to get the notifcation-objects for
     *
     * @return array an array of ContentElement-objects
     */
    function getNotificationObjects($course_id, $since, $user_id)
    {
        return false;
    }
}