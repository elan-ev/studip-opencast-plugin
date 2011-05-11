<?php
/*
 * admin.php - admin plugin controller
 * Copyright (c) 2010  André Klaßen
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once 'app/controllers/studip_controller.php';
require_once $this->trails_root.'/models/OCModel.php';
require_once $this->trails_root.'/models/OCRestClient.php';

class AdminController extends StudipController
{
    /**
     * Common code for all actions: set default layout and page title.
     */
    function before_filter(&$action, &$args)
    {
        $this->flash = Trails_Flash::instance();
        
        if($this->gconf = OCRestClient::getConfig(1)) {
            $this->occlient = new OCRestClient($this->gconf['series_url'], $this->gconf['user'], $this->gconf['password']);
        }
        

        // set default layout
        $layout = $GLOBALS['template_factory']->open('layouts/base');
        $this->set_layout($layout);
        
        $GLOBALS['CURRENT_PAGE'] =  'OpenCast Administration';
        Navigation::activateItem('/admin/config/');
    }

    /**
     * This is the default action of this controller.
     */
    function index_action()
    {

        if (isset($this->flash['message'])) {
            $this->message = $this->flash['message'];
        }
        
        
        $this->series = $this->occlient->getAllSeries();
        // We got all series so preserve their ids
        foreach ($this->series as $key => $serie) {
            OCRestClient::storeAllSeries($serie->seriesId[0]);
        }
 
    }
    
    function config_action()
    {
        if (isset($this->flash['message'])) {
            $this->message = $this->flash['message'];
        }
        
        
        if($this->gconf = OCRestClient::getConfig(1)) {
            $this->config_id  = $this->gconf['config_id'];
            $this->series_url = $this->gconf['series_url'];
            $this->search_url = $this->gconf['search_url'];
            $this->user = $this->gconf['user'];
            $this->password = $this->gconf['password'];
        } else {
            $this->config_id  = '1';
            $this->series_url = 'URL_TO_MATTERHORN';
            $this->search_url = 'URL_TO_MATTERHORN';
            $this->user = 'matterhorn_system_account';
            $this->password = 'CHANGE_ME';
        }
        
    }
    function update_config_action() {
        
        $this->config_id = Request::get('config_id');
        $this->series_url = Request::get('series_url');
        $this->search_url = Request::get('search_url');
        $this->user = Request::get('user');
        $this->password = Request::get('password');
        
        OCRestClient::setConfig($this->config_id, $this->series_url, $this->search_url, $this->user, $this->password);
        $success = _("Änderungen wurden erflolgreich übernommen.");
        $update = 0;
        $this->series = $this->occlient->getAllSeries();
        foreach ($this->series as $key => $serie) {
            
            if(OCRestClient::storeAllSeries($serie->seriesId[0])) {
               $update+=1;
            }

        }
        $this->flash['success'] = $update > 0 ? $success . sprintf(_(" Es wurden %s neue Series gefunden und hinzugefügt."), $update) : $success;
        
        $this->redirect(PluginEngine::getLink('opencast/admin/config'));
        
        
    }
}
?>
