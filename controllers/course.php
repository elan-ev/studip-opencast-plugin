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
require_once $this->trails_root.'/classes/OCRestClient/IngestClient.php';
require_once $this->trails_root.'/models/OCModel.php';

class CourseController extends StudipController
{
    /**
     * Common code for all actions: set default layout and page title.
     */
    function before_filter(&$action, &$args)
    {
        $this->flash = Trails_Flash::instance();

        // set default layout
        $layout = $GLOBALS['template_factory']->open('layouts/base');
        $this->set_layout($layout);

        $GLOBALS['CURRENT_PAGE'] = $_SESSION['SessSemName'][0] . ' - Opencast Player';
        
        if(($this->search_conf = OCRestClient::getConfig('search')) && ($this->series_conf = OCRestClient::getConfig('schedule'))
                && ($this->scheduler_conf = OCRestClient::getConfig('series'))) {
            $this->series_client = new OCRestClient($this->series_conf['service_url'], $this->series_conf['service_user'], $this->series_conf['service_password']);
            $this->search_client = new OCRestClient($this->search_conf['service_url'], $this->search_conf['service_user'], $this->search_conf['service_password']);
            $this->scheduler_client = new OCRestClient($this->scheduler_conf['service_url'], $this->scheduler_conf['service_user'], $this->scheduler['service_password']);
        } elseif (!$this->search_client->getAllSeries()) {
             $this->flash['error'] = _("Es besteht momentan keine Verbindung zum Search Service");
        } else {
            throw new Exception(_("Die Verknüpfung  zum Opencast Matterhorn Server wurde nicht korrekt durchgeführt."));
        }
        // take care of the navigation icon
        $navigation = Navigation::getItem('/course/opencast');
        $this->imgagepath = '../../'.$this->dispatcher->trails_root.'/images/online-prev.png';
        $navigation->setImage('../../'.$this->dispatcher->trails_root.'/images/oc-logo-black.png');
    }

    /**
     * This is the default action of this controller.
     */
    function index_action($active_id = '')
    {

        // set layout for index page
        $layout = $GLOBALS['template_factory']->open('layouts/base_without_infobox');
        $this->set_layout($layout);

        $search_client = new SearchClient();

        Navigation::activateItem('course/opencast/overview');

        if (isset($this->flash['message'])) {
            $this->message = $this->flash['message'];
        }
        $this->course_id = $_SESSION['SessionSeminar'];

        // lets get all episodes for the connected series
        if ($cseries = OCModel::getConnectedSeries($this->course_id) && !isset($this->flash['error'])) {


            $this->episode_ids = array();
            $ids = array();
            $count = 0;
            $this->search_client = new SearchClient();

            foreach(OCModel::getConnectedSeries($this->course_id) as $serie) {
               // var_dump($serie['series_id']); die;
                if ($series = $search_client->getEpisodes($serie['series_id'])){


                    foreach($series as $episode) {
                        $visibility = OCModel::getVisibilityForEpisode($this->course_id, $episode->id);
                        if(is_object($episode->mediapackage) && true){//$visibility['visible']){
                            $count+=1;
                            //var_dump($episode);
                            $ids[] = $episode->id;
                            $this->episode_ids[] = array('id' => $episode->id,
                                'title' => $episode->dcTitle,
                                'start' => $episode->mediapackage->start,
                                'duration' => $episode->mediapackage->duration,
                                'description' => $episode->dcDescription,
                                'author' => $episode->dcCreator
                            );
                        }
                    }


                } else {
                    $this->flash['error'] = _("Es besteht momentan keine Verbindung zum Series Service");
                }
            }
        }

        if($active_id) {
            $this->active_id = $active_id;
        } else {
            $this->active_id = $this->episode_ids[0][id];
        }


        if($count > 0) {
            $this->embed = $this->search_conf['service_url'] ."/engage/ui/embed.html?id=".$this->active_id;
            $this->engage_player_url = 'http://'. $this->search_conf['service_url']."/engage/ui/watch.html?id=".$this->active_id;
        }

    }
    
    function config_action()
    {

        if (isset($this->flash['message'])) {
            $this->message = $this->flash['message'];
        }
        $this->search_client = new SearchClient();
        
        Navigation::activateItem('course/opencast/config');
        $navigation = Navigation::getItem('/course/opencast');
        $navigation->setImage('../../'.$this->dispatcher->trails_root.'/images/oc-logo-black.png');


        
        $this->course_id = $_SESSION['SessionSeminar'];

        $this->series_client = new SeriesClient();
        $this->series = $this->series_client->getAllSeries();


        //$this->series = OCModel::getUnconnectedSeries();
        //$sem = new Seminar($this->course_id);


        $this->cseries = OCModel::getConnectedSeries($this->course_id);


        if(!$this->cseries) {
            $this->rseries = $this->series;

        } elseif(count($this->cseries) > 0) {
            $this->connected = false;
            $serie= $this->cseries;
            $serie = array_pop($serie);

            $this->serie_name = $this->series_client->getSeries($serie['series_id']);



            $this->serie_id = $serie['series_id'];
            if($serie['schedule'] == 1){
                $this->dates  = OCModel::getDates($this->course_id);
            } else {
                $this->connected = true;

                if ($series = $this->search_client->getSeries($serie['series_id'])){
                    $x = 'search-results';
                    if($series->$x->total > 0) {
                        $this->episodes = $series->$x->result;
                    }
                }
            }

        }

    }
    
    function edit_action($course_id)
    {   
        $series = Request::getArray('series');
        foreach( $series as $serie) {
            OCModel::setSeriesforCourse($course_id, $serie);
        }
        $this->flash['message'] = _("Änderungen wurden erflolgreich übernommen");
        $this->redirect(PluginEngine::getLink('opencast/course/config'));
    }
    
    function remove_series_action($series_id, $delete_series = false,$approveRemoval = false, $studipticket = false)
    {
        $course_id = Request::get('cid');
        $series_client = new SeriesClient();

        if($approveRemoval  && check_ticket($studipticket)) {

            OCModel::removeSeriesforCourse($course_id, $series_id);

            if($delete_series == 'true') {
                $series_client->removeSeries($series_id);
            }

            $this->flash['message'] = _("Zuordnung wurde entfernt");
            $this->redirect(PluginEngine::getLink('opencast/course/config'));
            return;
        } else {
            $template = $GLOBALS['template_factory']->open('shared/question');
            $template->set_attribute('approvalLink',PluginEngine::getLink('opencast/course/remove_series/' . $series_id . '/'. $delete_series . '/true/' . get_ticket()));
            $template->set_attribute('disapprovalLink',PluginEngine::getLink('opencast/course/config'));
            $template->set_attribute('question', _("Sind Sie sicher, dass Sie diese Series löschen möchten?"));

            $this->flash['question'] = $template->render();
            $this->redirect(PluginEngine::getLink('opencast/course/config'));
            return;
        }
    }


    function scheduler_action()
    {
        require_once 'lib/raumzeit/raumzeit_functions.inc.php';
        Navigation::activateItem('course/opencast/scheduler');
        $navigation = Navigation::getItem('/course/opencast');
        $navigation->setImage('../../'.$this->dispatcher->trails_root.'/images/oc-logo-black.png');

        $this->course_id = $_SESSION['SessionSeminar'];
        $this->cseries = OCModel::getConnectedSeries($this->course_id);


        $this->dates  =  OCModel::getDates($this->course_id);



    }


    function schedule_action($resource_id, $termin_id)
    {


        $this->course_id = Request::get('cid');
        $scheduler_client = new SchedulerClient();

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

        $scheduler_client = new SchedulerClient();

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
        $this->series_client = new SeriesClient();

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
           $this->redirect(PluginEngine::getLink('opencast/course/config'));
        } else {
           OCModel::setVisibilityForEpisode($this->course_id, $episode_id, 'true');
           $this->flash['message'] = _("Episode wurde sichtbar geschaltet");
           $this->redirect(PluginEngine::getLink('opencast/course/config'));
        }
    }

    function upload_action()
    {
        //TODO: gibt es keine generische Funktion dafür?
        $this->rel_canonical_path = $GLOBALS['CANONICAL_RELATIVE_PATH_STUDIP'] . 'plugins_packages/elan-ev/OpenCast';
        Navigation::activateItem('course/opencast/upload');
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


}
?>
