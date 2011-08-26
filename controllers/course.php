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
        $navigation = Navigation::getItem('/course/opencast');
        $navigation->setImage('../../'.$this->dispatcher->trails_root.'/images/oc-logo-black.png');




        if (isset($this->flash['message'])) {
            $this->message = $this->flash['message'];
        }
        $this->course_id = $_SESSION['SessionSeminar'];

        // lets get all episodes for the connected series
        if ($cseries = OCModel::getConnectedSeries($this->course_id) && !isset($this->flash['error'])) {
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
        $navigation = Navigation::getItem('/course/opencast');
        $navigation->setImage('../../'.$this->dispatcher->trails_root.'/images/oc-logo-black.png');


        
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

    function schedule_action($resource_id, $termin_id)
    {
        $this->course_id = Request::get('cid');

        if( OCModel::scheduleRecording($this->course_id, $resource_id, $termin_id)) {
            $this->flash['message'] = _("Aufzeichnung wurde geplant.");
        } else {
            $this->flash['error'] = _("Aufzeichnung konnte nicht geplant werden.");
        }

        
        $this->redirect(PluginEngine::getLink('opencast/course/config'));
    }


    function create_series_action()
    {
        $this->course_id = Request::get('cid');



        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
    <series>
        <description> A-rather-long-description</description>
        <additionalMetadata>
            <metadata>
                <key>title</key>
                <value>OCPlugin Demo title</value>
            </metadata>
            <metadata>
                <key>license</key>
                <value>demo</value>
            </metadata>
            <metadata>
                <key>valid</key>
                <value>1314196388195</value>
            </metadata>
            <metadata>
                <key>publisher</key>
                <value>demo</value>
            </metadata>
            <metadata>
                <key>creator</key>
                <value>demo</value>
            </metadata>
            <metadata>
                <key>subject</key>
                <value>demo</value>
            </metadata>
            <metadata>
                <key>temporal</key>
                <value>demo</value>
            </metadata>
            <metadata>
                <key>audience</key>
                <value>demo</value>
            </metadata>
            <metadata>
                <key>spatial</key>
                <value>demo</value>
            </metadata>
            <metadata>
                <key>rightsHolder</key>
                <value>demo</value>
            </metadata>
            <metadata>
                <key>extent</key>
                <value>3600000</value>
            </metadata>
            <metadata>
                <key>created</key>
                <value>1314196388195</value>
            </metadata>
            <metadata>
                <key>language</key>
                <value>demo</value>
            </metadata>
            <metadata>
                <key>isReplacedBy</key>
                <value>demo</value>
            </metadata>
            <metadata>
                <key>type</key>
                <value>demo</value>
            </metadata>
            <metadata>
                <key>available</key>
                <value>1314196388195</value>
            </metadata>
            <metadata>
                <key>modified</key>
                <value>1314196388195</value>
            </metadata>
            <metadata>
                <key>replaces</key>
                <value>demo</value>
            </metadata>
            <metadata>
                <key>contributor</key>
                <value>demo</value>
            </metadata>
            <metadata>
                <key>issued</key>
                <value>1314196388195</value>
            </metadata>
        </additionalMetadata>
    </series>';


        $post = array('series' => $xml);


        $this->matterhorn_base_url = $this->series_conf['service_url'];
        $this->username = $this->series_conf['service_user'];
        $this->password = $this->series_conf['service_password'];
        $rest_end_point = "/series/?_method=put&";
        $uri = $rest_end_point;
        // setting up a curl-handler

        $this->ochandler = curl_init();
        curl_setopt($this->ochandler, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ochandler,CURLOPT_URL,$this->matterhorn_base_url.$uri);
        curl_setopt($this->ochandler, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
        curl_setopt($this->ochandler, CURLOPT_USERPWD, 'matterhorn_system_account'.':'.'CHANGE_ME');
        curl_setopt($this->ochandler, CURLOPT_HTTPHEADER, array("X-Requested-Auth: Digest"));

        curl_setopt($this->ochandler, CURLOPT_POST, true);
        curl_setopt($this->ochandler, CURLOPT_POSTFIELDS, $post);

        
        $response = curl_exec($this->ochandler);
        $httpCode = curl_getinfo($this->ochandler, CURLINFO_HTTP_CODE);

        if ($httpCode == 201){

            var_dump($response); die();

            $this->flash['message'] = _("Series wurde angelegt");
            $this->redirect(PluginEngine::getLink('opencast/course/config'));
        } else {
            var_dump($httpCode);die();
        }

   









    }

}
?>
