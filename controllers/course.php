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
        
        if(($this->search_conf = OCRestClient::getConfig('search')) && ($this->series_conf = OCRestClient::getConfig('series'))) {
            $this->series_client = new OCRestClient($this->series_conf['service_url'], $this->series_conf['service_user'], $this->series_conf['service_password']);
            $this->search_client = new OCRestClient($this->search_conf['service_url'], $this->search_conf['service_user'], $this->search_conf['service_password']);
        } elseif (!$this->search_client->getAllSeries()) {
             $this->flash['error'] = _("Es besteht momentan keine Verbindung zum Search Service");
        } else {
            throw new Exception(_("Die Verknüpfung  zum Opencast Matterhorn Server wurde nicht korrekt durchgeführt."));
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

        // lets get all episodes for the connected series
        if ($cseries = OCModel::getConnectedSeries($course_id) && !isset($this->flash['error'])) {
            $this->episode_ids = array();
            $ids = array();
            foreach($cseries as $serie) {
                 if ($series[] = $this->search_client->getEpisode($serie['series_id'])){
                     $x = 'search-results';
                     foreach($series[0]->$x->result as $episode) {
                        if(is_object($episode->mediapackage)) {
                            $ids[] = $episode->id;
                            $this->episode_ids[] = array('id' => $episode->id,
                                                            'title' => $episode->dcTitle,
                                                            'start' => $episode->mediapackage->start,
                                                            'duration' => $episode->mediapackage->duration,
                                                            'description' => ''
                                                       );
                            }
                     }

                 }  else {
                    $this->flash['error'] = _("Es besteht momentan keine Verbindung zum Series Service");
                 }
            }
            
            if($active_id) {
                $this->active_id = $active_id;
            } else {
                $this->active_id = $this->episode_ids[0][id];
            }
            $this->embed = $this->search_conf['service_url'] ."/engage/ui/embed.html?id=".$this->active_id;
        }
    }
    
    function config_action()
    {
        require_once 'lib/raumzeit/raumzeit_functions.inc.php';
        if (isset($this->flash['message'])) {
            $this->message = $this->flash['message'];
        }
        Navigation::activateItem('course/opencast/config');
        
        $this->course_id = $_SESSION['SessionSeminar'];
        //$this->series = $this->occlient->getAllSeries();
        $this->series = OCModel::getUnconnectedSeries();

        $this->cseries = OCModel::getConnectedSeries($this->course_id);

        $this->rseries = array_diff($this->series, $this->cseries);


        // let's fiddle around with the seminar and hava look

        $sem = new Seminar($this->course_id);

        /*
         *  termin id aus termin tabelle ziehen
         *  SingleDate($termin_id);
         *
         *
         */

         $this->dates  = OCModel::getDates($this->course_id);

         




        /*
        $this->dates =  $sem->getUndecoratedData();

        $this->termine = getAllSortedSingleDates($sem);

        $this->issues =  $sem->getIssues();


        //var_dump($this->series,$this->cseries,$this->rseries); die;
         
         */
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
    
    function remove_series_action($course_id, $series_id)
    {
        OCModel::removeSeriesforCourse($course_id, $series_id);
        $this->flash['message'] = _("Zuordnung wurde entfernt");
        $this->redirect(PluginEngine::getLink('opencast/course/config'));
    }

}
?>
