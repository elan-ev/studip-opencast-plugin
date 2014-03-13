<?php
/*
 * course.php - course controller
 * Copyright (c) 2010  André Klaßen
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once 'app/controllers/studip_controller.php';
//require_once $this->trails_root.'/models/OCRestClient.php';
require_once $this->trails_root.'/classes/OCRestClient/SearchClient.php';
require_once $this->trails_root.'/classes/OCRestClient/SeriesClient.php';
require_once $this->trails_root.'/classes/OCRestClient/SchedulerClient.php';
require_once $this->trails_root.'/classes/OCRestClient/UploadClient.php';
require_once $this->trails_root.'/classes/OCRestClient/IngestClient.php';
require_once $this->trails_root.'/classes/OCRestClient/WorkflowClient.php';
require_once $this->trails_root.'/classes/OCRestClient/MediaPackageClient.php';
require_once $this->trails_root.'/models/OCModel.php';

class CourseController extends StudipController
{
    
    /**
     * Sets the page title. Page title always includes the course name.
     *
     * @param mixed $title Title of the page (optional)
     */
    private function set_title($title = '')
    {
        $title_parts   = func_get_args();
        $title_parts[] = $GLOBALS['SessSemName']['header_line'];
        $title_parts =  array_reverse($title_parts);
        $page_title    = implode(' - ', $title_parts);
        PageLayout::setTitle($page_title);
    }
    
    
    
    
    /**
     * Common code for all actions: set default layout and page title.
     */
    function before_filter(&$action, &$args)
    {
        $this->flash = Trails_Flash::instance();

        // set default layout
        $layout = $GLOBALS['template_factory']->open('layouts/base');
        $this->set_layout($layout);
        $this->pluginpath = $this->dispatcher->trails_root;
    }

    /**
     * This is the default action of this controller.
     */
    function index_action($active_id = '')
    {
         global  $user;
        /*
         * Add some JS and CSS
         *
         */
        $style_attributes = array(
            'rel'   => 'stylesheet',
            'href'  => $GLOBALS['CANONICAL_RELATIVE_PATH_STUDIP'] . $this->pluginpath . '/vendor/tipTip.css');
        PageLayout::addHeadElement('link',  array_merge($style_attributes, array()));

        $script_attributes = array(
            'src'   => $GLOBALS['CANONICAL_RELATIVE_PATH_STUDIP'] . $this->pluginpath . '/vendor/slimScroll.js');
        PageLayout::addHeadElement('script', $script_attributes, '');

        $script_attributes = array(
            'src'   => $GLOBALS['CANONICAL_RELATIVE_PATH_STUDIP'] . $this->pluginpath . '/vendor/jquery.tipTip.minified.js');
        PageLayout::addHeadElement('script', $script_attributes, '');

        $this->set_title(_("Opencast Player"));

        // set layout for index page
        $layout = $GLOBALS['template_factory']->open('layouts/base_without_infobox');
        $this->set_layout($layout);

        Navigation::activateItem('course/opencast/overview');
        try {
            $this->search_client = SearchClient::getInstance();
            if (isset($this->flash['message'])) {
                $this->message = $this->flash['message'];
            }
            $this->course_id = $_SESSION['SessionSeminar'];

            // lets get all episodes for the connected series
            if (($cseries = OCSeriesModel::getConnectedSeries($this->course_id)) && !isset($this->flash['error'])) {

                $this->episode_ids = array();
                $ids = array();
                $count = 0;
                $this->search_client = SearchClient::getInstance();

                    foreach($cseries as $serie) {
                        $series = $this->search_client->getEpisodes($serie['identifier']);
                        if(!empty($series)) {
                            foreach($series as $episode) {
                                $visibility = OCModel::getVisibilityForEpisode($this->course_id, $episode->id);
                                if(is_object($episode->mediapackage) && $visibility['visible']!= 'false' ){
                                    $count+=1;
                                    $ids[] = $episode->id;
                                    $this->episode_ids[] = array('id' => $episode->id,
                                        'title' => $episode->dcTitle,
                                        'start' => $episode->mediapackage->start,
                                        'duration' => $episode->mediapackage->duration,
                                        'description' => $episode->dcDescription,
                                        'author' => $episode->dcCreator,
                                        'preview' => $episode->mediapackage->attachments->attachment[0]->url
                                    );
                                }
                            }
                        }
                    }
            }

            if($active_id) {
                $this->active_id = $active_id;
            } else {
                $this->active_id = $this->episode_ids[0][id];
            }


            if($count > 0) {
                $this->embed = $this->search_client->getBaseURL() ."/engage/ui/embed.html?id=".$this->active_id;
                $this->engage_player_url = 'http://' . $this->search_client->getBaseURL() ."/engage/ui/watch.html?id=".$this->active_id.'&studipuser='.$user->username;
            }
        } catch (Exception $e) {
            $this->flash['error'] = $e->getMessage();
            $this->render_action('_error');
        }

    }
    
    function config_action()
    {
        if (isset($this->flash['message'])) {
            $this->message = $this->flash['message'];
        }
        Navigation::activateItem('course/opencast/config');
        $navigation = Navigation::getItem('/course/opencast');
        $navigation->setImage('../../'.$this->dispatcher->trails_root.'/images/oc-logo-black.png');
        $this->course_id = $_SESSION['SessionSeminar'];
        $this->set_title(_("Opencast Konfiguration"));
        
        
        $this->connectedSeries = OCSeriesModel::getConnectedSeries($this->course_id);
        $this->connectedSeries = OCSeriesModel::getConnectedSeries($this->course_id);
        $this->unconnectedSeries = OCSeriesModel::getUnconnectedSeries($this->course_id, true);
        
        /*
        
        try {
    
           //  $this->connectedSeries = OCSeriesModel::getConnectedSeries($this->course_id);
            
        //    $this->connectedSeries = OCSeriesModel::getConnectedSeries($this->course_id);
      
            //$this->unconnectedSeries = OCSeriesModel::getUnconnectedSeries($this->course_id, true);
            
           // $allseries = OCSeriesModel::getAllSeries();
                    //$this->search_client = SearchClient::getInstance();
    
            
        } catch (Exception $e) {
            $this->flash['error'] = $e->getMessage();
            $this->render_action('_error');
        }*/

    }
    
    function edit_action($course_id)
    {   
        
     
        $series = Request::getArray('series');
        
    
        foreach( $series as $serie) {
            OCSeriesModel::setSeriesforCourse($course_id, $serie);
        }
        $this->flash['message'] = _("Änderungen wurden erfolgreich übernommen");
        $this->redirect(PluginEngine::getLink('opencast/course/config'));
    }
    
    function remove_series_action($course_id, $series_id)
    {
        
        $schedule_episodes = OCSeriesModel::getScheduledEpisodes($course_id);
        
        OCSeriesModel::removeSeriesforCourse($course_id, $series_id);
        
        
        
        /*
        $series_client = SeriesClient::getInstance();
        $series_client->removeSeries($series_id); 
        */
        
        $this->flash['message'] = _("Zuordnung wurde entfernt");
        $this->redirect(PluginEngine::getLink('opencast/course/config'));
    }


    function scheduler_action()
    {
        require_once 'lib/raumzeit/raumzeit_functions.inc.php';
        Navigation::activateItem('course/opencast/scheduler');
        $navigation = Navigation::getItem('/course/opencast');
        $navigation->setImage('../../'.$this->dispatcher->trails_root.'/images/oc-logo-black.png');
        
        $this->set_title(_("Opencast Aufzeichnungen verwalten"));
        

        $this->course_id = $_SESSION['SessionSeminar'];
        
        $this->cseries = OCModel::getConnectedSeries($this->course_id);
        $this->dates  =  OCModel::getFutureDates($this->course_id);
        
        $search_client = SearchClient::getInstance();
        
        
        $workflow_client = WorkflowClient::getInstance();
        
        
        
        // lets get all episodes for the connected series
        if (($cseries = OCSeriesModel::getConnectedSeries($this->course_id)) && !isset($this->flash['error'])) {

            $this->episode_ids = array();
            $ids = array();
            $count = 0;
            $this->search_client = SearchClient::getInstance();
                foreach($cseries as $serie) {
                   // $instances = $workflow_client->getInstances($serie['identifier']);
                    $this->episodes = $search_client->getEpisodes($serie['identifier']);
                }
        }


    }


    function schedule_action($resource_id, $termin_id)
    {


        $this->course_id = Request::get('cid');
        $scheduler_client = SchedulerClient::getInstance();

        if($scheduler_client->scheduleEventForSeminar($this->course_id, $resource_id, $termin_id)) {
            $this->flash['message'] = _("Aufzeichnung wurde geplant.");
        } else {
            $this->flash['error'] = _("Aufzeichnung konnte nicht geplant werden.");
        }

        
        $this->redirect(PluginEngine::getLink('opencast/course/scheduler'));
    }

    function unschedule_action($resource_id, $termin_id)
    {

        $this->course_id = Request::get('cid');

        $scheduler_client = SchedulerClient::getInstance();
        

        if( $scheduler_client->deleteEventForSeminar($this->course_id, $resource_id, $termin_id)) {
            $this->flash['message'] = _("Die geplante Aufzeichnung wurde entfernt");
        } else {
            $this->flash['error'] = _("Die geplante Aufzeichnung konnte nicht entfernt werden.");
        }


        $this->redirect(PluginEngine::getLink('opencast/course/scheduler'));
    }


    function update_action($resource_id, $termin_id)
    {

        $this->course_id = Request::get('cid');

        if( $this->scheduler_client->updateEventForSeminar($this->course_id, $resource_id, $termin_id)) {
            $this->flash['message'] = _("Die geplante Aufzeichnung aktualisiert");
        } else {
            $this->flash['error'] = _("Die geplante Aufzeichnung konnte nicht aktualisiert werden.");
        }


        $this->redirect(PluginEngine::getLink('opencast/course/config'));
    }


    function create_series_action()
    {
        $this->course_id = Request::get('cid');
        $this->series_client = SeriesClient::getInstance();

        if($this->series_client->createSeriesForSeminar($this->course_id)) {
            $this->flash['message'] = _("Series wurde angelegt");
            $this->redirect(PluginEngine::getLink('opencast/course/config'));
        } else {
            throw new Exception("Verbindung zum Series-Service konnte nicht hergestellt werden.");
        }
    }

    function toggle_visibility_action($episode_id) {
        $this->course_id = Request::get('cid');
     
        $visible = OCModel::getVisibilityForEpisode($this->course_id, $episode_id);

        if($visible['visible'] == 'true'){
           OCModel::setVisibilityForEpisode($this->course_id, $episode_id, 'false');
           $this->flash['message'] = _("Episode wurde unsichtbar geschaltet");
           $this->redirect(PluginEngine::getLink('opencast/course/scheduler'));
        } else {
           OCModel::setVisibilityForEpisode($this->course_id, $episode_id, 'true');
           $this->flash['message'] = _("Episode wurde sichtbar geschaltet");
           $this->redirect(PluginEngine::getLink('opencast/course/scheduler'));
        }
    }

    function upload_action()
    {
        $this->date = date('Y-m-d');
        $this->hour = date('H');
        $this->minute = date('i');
        
        $scripts = array(
            '/vendor/jquery.fileupload.js',
            '/vendor/jquery.ui.widget.js'
        );
        Navigation::activateItem('course/opencast/upload');
        
        try {
            //check needed services before showing upload form
            UploadClient::getInstance()->checkService();
            IngestClient::getInstance()->checkService();
            MediaPackageClient::getInstance()->checkService();
            SeriesClient::getInstance()->checkService();

            foreach($scripts as $path) {
                $script_attributes = array(
                    'src'   => $GLOBALS['CANONICAL_RELATIVE_PATH_STUDIP'] . 'plugins_packages/elan-ev/OpenCast' . $path);
                PageLayout::addHeadElement('script', $script_attributes, '');
            }



            //TODO: gibt es keine generische Funktion dafür?
            $this->rel_canonical_path = $GLOBALS['CANONICAL_RELATIVE_PATH_STUDIP'] . 'plugins_packages/elan-ev/OpenCast';
        } catch (Exception $e) {
            $this->flash['error'] = $e->getMessage();
            $this->render_action('_error');
        }
    }


    function ingest_action()
    {
        global $UPLOAD_PATH;



        //check if an suitable upload dir exits
        if(!chdir( getcwd() .'/assets/opencast-uploads')) {
            mkdir($UPLOAD_PATH.  '/opencastupload');
            symlink($UPLOAD_PATH.  '/opencastupload', getcwd() .'/assets/opencast-uploads');
        } else {
            $target_path = $UPLOAD_PATH .'/opencastupload/'. basename( $_FILES['video']['name']);

            if(move_uploaded_file($_FILES['video']['tmp_name'], $target_path)) {
                // Passende message
                $this->flash['message'] = _("Das Video ");
                $video_uri = $GLOBALS['ABSOLUTE_URI_STUDIP'].'assets/opencast-uploads/'. $_FILES['video']['name'];

                //echo "The file ".  basename( $_FILES['video']['name']).
                    " has been uploaded";
                //echo "<img src='". $GLOBALS['ABSOLUTE_URI_STUDIP'].'assets/opencast-uploads/'. $_FILES['video']['name']."'>";
            } else{
                //pasende message
                //echo "There was an error uploading the file, please try again!";
            }
        }



        $this->redirect(PluginEngine::getLink('opencast/course/upload'));

    }
    
    function bulkschedule_action()
    {
        $course_id =  Request::get('cid');
        $action = Request::get('action');

        $dates = Request::getArray('dates');    
        foreach($dates as $termin_id => $resource_id){
            switch($action) {
                case "create":
                    $this->schedule($resource_id, $termin_id, $course_id);
                    break;
                case "update":
                    $this->updateschedule($resource_id, $termin_id, $course_id);
                    break;
                case "delete":
                    $this->unschedule($resource_id, $termin_id, $course_id);
                    break;
            }
        }

        $this->redirect(PluginEngine::getLink('opencast/course/scheduler'));
    }
    
    static function schedule($resource_id, $termin_id, $course_id) {
        $scheduled = OCModel::checkScheduledRecording($course_id, $resource_id, $termin_id);
        if(!$scheduled) {
            $scheduler_client = SchedulerClient::getInstance();

            if($scheduler_client->scheduleEventForSeminar($course_id, $resource_id, $termin_id)) {
                return true;
            } else {
                // TODO FEEDBACK
            }
        }
    }
    
    static function updateschedule($resource_id, $termin_id, $course_id) {
        $scheduled = OCModel::checkScheduledRecording($course_id, $resource_id, $termin_id);
        if($scheduled){
            $this->unschedule($resource_id, $termin_id, $course_id);
        }
        $this->schedule($resource_id, $termin_id, $course_id);
    }
    
    static function unschedule($resource_id, $termin_id, $course_id) {
        $scheduled = OCModel::checkScheduledRecording($course_id, $resource_id, $termin_id);
        if($scheduled) {
            $scheduler_client = SchedulerClient::getInstance();

            if( $scheduler_client->deleteEventForSeminar($course_id, $resource_id, $termin_id)) {
                return true;
            } else {
                // TODO FEEDBACK
            }
        }
    }


}
?>
