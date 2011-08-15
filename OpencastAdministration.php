<?php
/*
 * OpencastAdministration.class.php - The administarion of the opencast player
 * Copyright (c) 2010  André Klaßen
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once 'vendor/trails/trails.php';

class OpencastAdministration extends StudipPlugin implements AdministrationPlugin
{
    /**
     * Initialize a new instance of the plugin.
     */
    function __construct()
    {
        parent::__construct();

        global $SessSemName, $perm;
        
        if($perm->have_perm('admin')) {
            //.. now the subnavi
            $main = new Navigation(_("Opencast Administration"));
            // TODO think about an index page.. for the moment the config page is in charge..
            $main->setURL(PluginEngine::getURL('opencast/admin/config'));
    
            $config = new Navigation('OC Einstellungen');
            $config->setURL(PluginEngine::getURL('opencast/admin/config'));
            $main->addSubNavigation('oc-config', $config);

            $resources = new Navigation('OC Ressourcen');
            $resources->setURL(PluginEngine::getURL('opencast/admin/resources'));
            $main->addSubNavigation('oc-resources', $resources);
            
            Navigation::addItem('/start/opencast', $main);
            Navigation::addItem('/admin/config/oc-config', $config);
            Navigation::addItem('/admin/config/oc-resources', $resources);

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
}
