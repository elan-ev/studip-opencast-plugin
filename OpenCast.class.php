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
require_once 'models/OCSeriesModel.php';
require_once 'classes/OCRestClient/SearchClient.php';
require_once 'classes/OCRestClient/SeriesClient.php';


define('OC_UPLOAD_CHUNK_SIZE', '1000000');
define('OC_CLEAN_SESSION_AFTER_DAYS', '1');


class OpenCastBase extends StudipPlugin implements SystemPlugin, StandardPlugin
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
            
            
            $endpoints = new Navigation('OC Endpoints');
            $endpoints->setURL(PluginEngine::getURL('opencast/admin/endpoints'));
            $main->addSubNavigation('oc-endpoints', $endpoints);
            

            $resources = new Navigation('OC Ressourcen');
            $resources->setURL(PluginEngine::getURL('opencast/admin/resources'));
            $main->addSubNavigation('oc-resources', $resources);

            Navigation::addItem('/start/opencast', $main);
            Navigation::addItem('/admin/config/oc-config', $config);
            Navigation::addItem('/admin/config/oc-endpoints', $endpoints);
            Navigation::addItem('/admin/config/oc-resources', $resources);


        }
   

        PageLayout::addStylesheet($this->getpluginUrl() . '/stylesheets/oc.css');
        PageLayout::addScript($this->getPluginUrl() . '/javascripts/application.js');
        
        if($perm->have_perm('dozent')){
            PageLayout::addScript($this->getPluginUrl() . '/javascripts/embed.js');
            PageLayout::addStylesheet($this->getpluginUrl() . '/stylesheets/embed.css'); 
        }
        
        StudipFormat::addStudipMarkup('opencast', '\[opencast\]', '\[\/opencast\]', 'OpenCast::markupOpencast');
     
    }

    /**
     * This method dispatches all actions.
     *
     * @param string   part of the dispatch path that was not consumed
     */
    function perform($unconsumed_path)
    {
        $trails_root = $this->getPluginPath();
        $dispatcher = new Trails_Dispatcher($trails_root,
                                            rtrim(PluginEngine::getURL($this, null, ''), '/'),
                                            null);

        $dispatcher->plugin = $this;
        $dispatcher->dispatch($unconsumed_path);
    }

    /**
     * This method takes care of the Navigation
     *
     * @param string   course_id
     * @param string   last_visit
     */
    function getIconNavigation($course_id, $last_visit, $user_id = NULL)
    {
        return null;
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
 
    
        if (!$this->isActivated($course_id)) {
            return;
        }
        //.. now the subnavi
        $main = new Navigation("OpenCast");
        //$main = new Navigation("Veranstaltungsaufzeichnungen");
        $main->setURL(PluginEngine::getURL('opencast/course'));
        $main->setImage($this->getPluginUrl() . '/images/oc-logo.png');
        $main->setActiveImage($this->getPluginUrl() . '/images/oc-logo-black.png');

        $admin = new Navigation('Einstellungen');
        $admin->setURL(PluginEngine::getURL('opencast/course/config'));
        $overview = new Navigation('Aufzeichnungen');
        $overview->setURL(PluginEngine::getURL('opencast/course/index'));

        $scheduler = new Navigation('Aufzeichnungen planen');
        $scheduler->setURL(PluginEngine::getURL('opencast/course/scheduler'));

        $manager = new Navigation('Aufzeichnungen verwalten');
        $manager->setURL(PluginEngine::getURL('opencast/course/manage_episodes'));
        //$upload = new Navigation('Upload');
        //$upload->setURL(PluginEngine::getURL('opencast/course/upload'));
        $main->addSubNavigation('overview', $overview);

        
        if ($GLOBALS['perm']->have_studip_perm('admin', $course_id)) {
            // TODO: Add scheduler iff scheduling is allowed in current course
            $main->addSubNavigation('manager', $manager);
            $series_metadata = OCSeriesModel::getConnectedSeriesDB($course_id);
            if($series_metadata[0]['schedule'] == '1'){
                $main->addSubNavigation('scheduler', $scheduler);
                //$main->addSubNavigation('upload', $upload);
                
            }
            $main->addSubNavigation('config', $admin);

        }

        return array('opencast' => $main);
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
    
    static function markupOpencast  ($markup, $matches, $contents)
    {
        $search_client = SearchClient::getInstance();
        $engage_url =  parse_url($search_client->getBaseURL());
        $host = $engage_url['host'];
        $embed =  $host ."/engage/ui/embed.html?id=".$contents;
        
   	    return sprintf('<iframe src="https://%s" style="border:0px #FFFFFF none;" name="Opencast Matterhorn - Media Player" scrolling="no" frameborder="0" marginheight="0px" marginwidth="0px" width="540" height="404"></iframe><br>', $embed);
    }
    
    public function describeRoutes()
    {
        return array('/courses/:course_id/oc_episodes' => _('Opencast Matterhorn Aufzeichnungen einer Veranstaltung.'), );
    }

    public function routes(&$router)
    {

        $router->get('/courses/:course_id/oc_episodes', function($course_id) use ($router){
            
            try {
                $search_client = SearchClient::getInstance();
                if (($cseries = OCSeriesModel::getConnectedSeries($course_id))) {

                    foreach($cseries as $serie) {
                        $series = $search_client->getEpisodes($serie['identifier']);
                        if(!empty($series)) {
                            foreach($series as $episode) {
                                $visibility = OCModel::getVisibilityForEpisode($course_id, $episode->id);

                                if(is_object($episode->mediapackage) && $visibility['visible']!= 'false' ){
                                    $count+=1;
                                    $ids[] = $episode->id;
                                  
                                    foreach($episode->mediapackage->attachments->attachment as $attachment) {
                                        if($attachment->type === 'presenter/search+preview') $preview = $attachment->url;
                                    }
                                    
                                    foreach($episode->mediapackage->media->track as $track) {
                                        if(($track->type === 'presenter/delivery') && ($track->mimetype === 'video/mp4')){
                                            $url = parse_url($track->url);
                                            if(in_array('high-quality', $track->tags->tag) && $url['scheme'] != 'rtmp') {
                                               $presenter_download = $track->url;
                                            }
                                        }
                                        if(($track->type === 'presentation/delivery') && ($track->mimetype === 'video/mp4')){
                                            $url = parse_url($track->url);
                                            if(in_array('high-quality', $track->tags->tag) && $url['scheme'] != 'rtmp') {
                                               $presentation_download = $track->url;
                                            }
                                        }
                                        if(($track->type === 'presenter/delivery') && ($track->mimetype === 'audio/mp3'))
                                            $audio_download = $track->url;
                                            $engage_url =  parse_url($audio_download);
                                            $external_player_url = $engage_url['scheme']. '://' . $engage_url['host'] .    
                                            "/engage/ui/watch.html?id=".$episode->id;
                                    }
                                    
                                    $oc_episodes[$episode->id] = array('id' => $episode->id,
                                        'title' => $episode->dcTitle,
                                        'start' => $episode->mediapackage->start,
                                        'duration' => $episode->mediapackage->duration,
                                        'description' => $episode->dcDescription,
                                        'author' => $episode->dcCreator,
                                        'preview' => $preview,
                                        'external_player_url' => $external_player_url,
                                        'presenter_download' => $presenter_download,
                                        'presentation_download' => $presentation_download,
                                        'audio_download' => $audio_download
                                    );
                                }
                            }
                        }
                    }   
                }
            } catch (Exception $e) {
                die($e->getMessage());
            }
            if(empty($oc_episodes)){
                $router->halt(404, sprintf('Episodes for Course %s not found', $course_id));
            } else {
                header('Cache-Control: private');
                $router->expires('+360 minutes');
                $router->etag(md5(serialize($oc_episodes)));
                $router->render(compact('oc_episodes'));
            }
        })->conditions(array('course_id' => '|[a-f0-9]{32}|course'));;
    
    }
}

if (interface_exists('APIPlugin')) {
    class Opencast extends OpenCastBase implements APIPlugin {}
} else {
    class OpenCast extends OpenCastBase {}
}
