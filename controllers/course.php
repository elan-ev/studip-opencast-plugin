<?php
/*
 * course.php - course controller
 * Copyright (c) 2010  Andr� Kla�en
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once 'app/controllers/studip_controller.php';
require_once $this->trails_root.'/models/OCRestClient.php';
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
        
        if($this->gconf = OCRestClient::getConfig(1)) {
            $this->occlient = new OCRestClient($this->gconf['series_url'], $this->gconf['user'], $this->gconf['password']);
            $this->searchclient = new OCRestClient($this->gconf['search_url'], $this->gconf['user'], $this->gconf['password']);
            $series = $this->occlient->getAllSeries();
        } else {
            throw new Exception(_("Die Verkn�pfung  zum Opencast Matterhorn Server wurde nicht korrekt durchgef�hrt."));
        }
    }

    /**
     * This is the default action of this controller.
     */
    function index_action($active_id = '')
    {
        Navigation::activateItem('course/opencast/overview');

        if (isset($this->flash['message'])) {
            $this->message = $this->flash['message'];
        }
        $course_id = $_SESSION['SessionSeminar'];
        if ($cseries = OCModel::getConnectedSeries($course_id)) {
            $this->episode_ids = array();
            $ids = array();
            foreach($cseries as $serie) {
                 $series[] = $this->searchclient->getEpisode($serie['series_id']);
                 foreach($series as $episodes) {
                     foreach($episodes->result as $episode) {
                           if(!in_array((string)$episode[id],$ids, true)) {
                           $ids[] = (string)$episode[id];
                           $this->episode_ids[] = array('id' => $episode[id], 
                                                        'title' => $episode->mediapackage->title,
                                                        'start' => $episode->mediapackage[start],
                                                        'duration' => $episode->mediapackage[duration],
                                                        'description' => ''
                                                   );
                           }
                     }
                 }
            }
            
            if($active_id) {
                $this->active_id = $active_id;
            } else {
                $this->active_id = $this->episode_ids[0][id];
            }
            $this->embed = $this->gconf['search_url'] ."/engage/ui/embed.html?id=".$this->active_id;
        }
    }
    
    function config_action()
    {
        if (isset($this->flash['message'])) {
            $this->message = $this->flash['message'];
        }
        Navigation::activateItem('course/opencast/config');
        
        $this->course_id = $_SESSION['SessionSeminar'];
        
        $this->series = OCModel::getUnconnectedSeries();
        $this->cseries = OCModel::getConnectedSeries($this->course_id);
    }
    
    function edit_action($course_id)
    {   
        $series = Request::getArray('series');
        foreach( $series as $serie) {
            OCModel::setSeriesforCourse($course_id, $serie);
        }
        $this->flash['message'] = _("�nderungen wurden erflolgreich �bernommen");
        $this->redirect(PluginEngine::getLink('opencast/course/config'));
    }
    
    function remove_series_action($course_id, $series_id)
    {
        OCModel::removeSeriesforCourse($course_id, $series_id);
        $this->flash['message'] = _("Zuordnung wurde entfernt");
        $this->redirect(PluginEngine::getLink('opencast/course/config'));
    }

}
?>
