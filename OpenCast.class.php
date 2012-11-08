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
require_once 'models/OCModel.php';


class OpenCast extends StudipPlugin implements StandardPlugin
{
    /**
     * Initialize a new instance of the plugin.
     */
    function __construct()
    {
        parent::__construct();

        // do nothing if plugin is deactivated in this seminar/institute
        if (!$this->isActivated()) {
            return;
        }

        PageLayout::addScript($this->getPluginURL() . '/javascripts/application.js');
        PageLayout::addScript($this->getPluginURL() . '/javascripts/jquery.tipTip.minified.js');
        PageLayout::addScript($this->getPluginURL() . '/javascripts/slimScroll.js');
        PageLayout::addStylesheet($this->getPluginURL() . '/stylesheets/oc.css');
        PageLayout::addStylesheet($this->getPluginURL() . '/stylesheets/tipTip.css');

        if (!version_compare($GLOBALS['SOFTWARE_VERSION'], '2.3', '>')) {
            $navigation = $this->getTabNavigation(Request::get('cid', $GLOBALS['SessSemName'][1]));
            Navigation::addItem('/course/opencast', $navigation['opencast']);
        }
    }

    /**
     * This method dispatches all actions.
     *
     * @param string part of the dispatch path that was not consumed
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



    function getTabNavigation($course_id) {
        //global $SessSemName, $perm;


        $navigation = new Navigation(_('OpenCast'), PluginEngine::getLink('opencast/course'));
        $navigation->setImage('../../'.$this->getPluginPath().'/images/oc-logo.png');

        // add main third-level navigation-item
        $navigation->addSubNavigation('overview',     new Navigation(_('Aufzeichnungen'), PluginEngine::getLink('opencast/course/index')));
        $navigation->addSubNavigation('config', new Navigation(_('Einstellungen'), PluginEngine::getLink('opencast/course/config')));

        $cseries = OCModel::getConnectedSeries($course_id);
        if(is_array($cseries)) {
            $serie = array_pop($cseries);
            if($serie['schedule'] == 1) {
                $navigation->addSubNavigation('scheduler',    new Navigation(_('Aufzeichnungen Planen'), PluginEngine::getLink('opencast/course/scheduler')));
            }
        }
        return array('opencast' => $navigation);
    }




    function getIconNavigation($course_id, $last_visit, $user_id){

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
    
    function getInfoTemplate($course_id) {
    
    }


    /**
     * Return a warning message to be printed before deactivation of
     * this plugin in the given context.
     *
     * @param $context   context range id
     */
    public function deactivationWarning($context = null) {
        return _("Das Opencastplugin wurde deaktiviert.");
    }
    
    /* interface method */
      function getNotificationObjects($course_id, $since, $user_id)
      {
          return array();
      }

}
