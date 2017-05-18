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
require_once 'lib/log_events.inc.php';

require_once $this->trails_root.'/classes/OCRestClient/SearchClient.php';
require_once $this->trails_root.'/classes/OCRestClient/SeriesClient.php';
require_once $this->trails_root.'/classes/OCRestClient/SchedulerClient.php';
require_once $this->trails_root.'/classes/OCRestClient/UploadClient.php';
require_once $this->trails_root.'/classes/OCRestClient/IngestClient.php';
require_once $this->trails_root.'/classes/OCRestClient/WorkflowClient.php';
require_once $this->trails_root.'/classes/OCRestClient/MediaPackageClient.php';
require_once $this->trails_root.'/models/OCModel.php';
require_once $this->trails_root.'/models/OCCourseModel.class.php';


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
        
        PageLayout::addScript($GLOBALS['ocplugin_path']  . '/vendor/jquery.fileupload.js');
        PageLayout::addScript($GLOBALS['ocplugin_path']  . '/vendor/jquery.simplePagination.js');
        PageLayout::addScript($GLOBALS['ocplugin_path']  . '/vendor/circle-progress/circle-progress.js');
        PageLayout::addScript($GLOBALS['ocplugin_path']  . '/vendor/listjs/list.min.js');




        // set default layout
        $layout = $GLOBALS['template_factory']->open('layouts/base');
        $this->set_layout($layout);
        $this->pluginpath = $this->dispatcher->trails_root;
        $this->course_id = $_SESSION['SessionSeminar'];
        
        // notify on trails action
        $klass = substr(get_called_class(), 0, -10);
        $name = sprintf('oc_course.performed.%s_%s', $klass, $action);
        NotificationCenter::postNotification($name, $this);
        // change this variable iff theodulplayer is active
        $this->theodul = true;
        
    }

    /**
     * This is the default action of this controller.
     */
    function index_action($active_id = 'false', $upload_message = false)
    {

        $layout = $GLOBALS['template_factory']->open('layouts/base_without_infobox');
        $this->set_layout($layout);

        $this->set_title(_("Opencast Player"));
        if($upload_message == 'true') {
            $this->flash['messages'] = array('success' =>_('Die Datei wurden erfolgreich hochgeladen. Je nach Größe der Datei und Auslastung des Opencast Matterhorn-Server kann es einige Zeit in Anspruch nehmen, bis die entsprechende Aufzeichnung in der Liste sichtbar wird.'));
        }

        $reload = false;
        // set layout for index page
        $this->states = false;
        if(!$GLOBALS['perm']->have_studip_perm('dozent', $this->course_id)) {

            $layout = $GLOBALS['template_factory']->open('layouts/base_without_infobox');
            $this->set_layout($layout);
        } else {

            // Config-Dialog
            $this->connectedSeries = OCSeriesModel::getConnectedSeries($this->course_id, true);
            $this->unconnectedSeries = OCSeriesModel::getUnconnectedSeries($this->course_id, true);
            $this->workflow_client = WorkflowClient::getInstance();
            $workflow_ids = OCModel::getWorkflowIDsforCourse($this->course_id);

            $this->series_metadata = OCSeriesModel::getConnectedSeriesDB($this->course_id);
            if(!empty($workflow_ids)){

                foreach($workflow_ids as $workflow_id) {
                    $resp = $this->workflow_client->getWorkflowInstance($workflow_id['workflow_id']);
                    if($resp->state == 'SUCCEEDED') {
                        OCModel::removeWorkflowIDforCourse($workflow_id['workflow_id'], $this->course_id);
                        $reload = true;
                    } else $this->states[$workflow_id['workflow_id']] = $resp;
                }
            }

            //workflow
            $occourse = new OCCourseModel($this->course_id);
            $this->workflow_client = WorkflowClient::getInstance();
            $this->tagged_wfs = $this->workflow_client->getTaggedWorkflowDefinitions();

            $this->schedulewf = $occourse->getWorkflow('schedule');
            $this->uploadwf = $occourse->getWorkflow('upload');

        }

        Navigation::activateItem('course/opencast/overview');
        try {
                $this->search_client = SearchClient::getInstance();

                $occourse = new OCCourseModel($this->course_id);
                $this->coursevis = $occourse->getSeriesVisibility();

                if($occourse->getSeriesID()){

                    $ordered_episode_ids = $occourse->getEpisodes($reload);
                    if(!$GLOBALS['perm']->have_studip_perm('dozent', $this->course_id)) {
                        $this->ordered_episode_ids = $occourse->refineEpisodesForStudents($ordered_episode_ids);
                    } else {
                        $this->ordered_episode_ids = $ordered_episode_ids;
                    }

                    if(empty($active_id) || $active_id != "false") {
                        $this->active_id = $active_id;
                    } else if(isset($this->ordered_episode_ids)){
                        $first = current($this->ordered_episode_ids);
                        $this->active_id = $first['id'];
                    }

                    if(!empty($this->ordered_episode_ids) || true) {
                        $engage_url =  parse_url($this->search_client->getBaseURL());

                        if($this->theodul) {
                            $this->embed =  $this->search_client->getBaseURL() ."/engage/theodul/ui/core.html?id=".$this->active_id."&mode=embed";
                        } else {
                            $this->embed =  $this->search_client->getBaseURL() ."/engage/ui/embed.html?id=".$this->active_id;
                        }
                        // check whether server supports ssl
                        $embed_headers = @get_headers("https://". $this->embed);
                        if($embed_headers) {
                            $this->embed = "https://". $this->embed;
                        } else {
                            // not so nice fix for UOL
                            //$this->embed = "https://". $this->embed;
                            $this->embed = "http://". $this->embed;
                        }
                        $this->engage_player_url = $this->search_client->getBaseURL() ."/engage/ui/watch.html?id=".$this->active_id;
                    }

                    // Upload-Dialog
                    $this->date = date('Y-m-d');
                    $this->hour = date('H');
                    $this->minute = date('i');

                    //check needed services before showing upload form
                    UploadClient::getInstance()->checkService();
                    IngestClient::getInstance()->checkService();
                    MediaPackageClient::getInstance()->checkService();
                    SeriesClient::getInstance()->checkService();



                    // Remove Series
                    if($this->flash['cand_delete']) {
                        $this->flash['delete'] = true;
                    }
                } else {

                }
        } catch (Exception $e) {
            $this->flash['error'] = $e->getMessage();
            $this->render_action('_error');
        }
    }
    
    function config_action()
    {
        if (isset($this->flash['messages'])) {
            $this->message = $this->flash['messages'];
        }
        Navigation::activateItem('course/opencast/config');
        $navigation = Navigation::getItem('/course/opencast');
        $navigation->setImage('../../'.$this->dispatcher->trails_root.'/images/oc-logo-black.png');
        $this->course_id = $_SESSION['SessionSeminar'];
        $this->set_title(_("Opencast Konfiguration"));
        
  
        $this->connectedSeries = OCSeriesModel::getConnectedSeries($this->course_id);
        $this->unconnectedSeries = OCSeriesModel::getUnconnectedSeries($this->course_id, true);

    }
    
    function edit_action($course_id)
    {   

        $series = Request::getArray('series');
        foreach( $series as $serie) {
            OCSeriesModel::setSeriesforCourse($course_id, $serie, 'visible', 0, time());
            log_event('OC_CONNECT_SERIES',$serie, $course_id);
        }
        $this->flash['messages'] = array('success'=> _("Änderungen wurden erfolgreich übernommen. Es wurde eine Serie für den Kurs verknüpft."));

        $this->redirect(PluginEngine::getLink('opencast/course/index'));
    }
    
    function remove_series_action($ticket)
    {



        $course_id = Request::get('course_id');
        $series_id = Request::get('series_id');
        $delete = Request::get('delete');
        if( $delete && check_ticket($ticket)) {
            
            $scheduled_episodes = OCSeriesModel::getScheduledEpisodes($course_id);

            OCSeriesModel::removeSeriesforCourse($course_id, $series_id);

            /* Uncomment iff you really want to remove this series from the OC Core
            $series_client = SeriesClient::getInstance();
            $series_client->removeSeries($series_id); 
            */
            $this->flash['messages'] = array('success'=> _("Die Zuordnung wurde entfernt"));

            log_event('OC_REMOVE_CONNECTED_SERIES',$series_id, $course_id);
        }
        else{
            $this->flash['messages']['error'] = _("Die Zuordnung konnte nicht entfernt werden.");
        }
        
        $this->flash['cand_delete'] = true;
        
        $this->redirect(PluginEngine::getLink('opencast/course/index'));
    }


    function scheduler_action()
    {
        require_once 'lib/raumzeit/raumzeit_functions.inc.php';
        Navigation::activateItem('course/opencast/scheduler');
        $navigation = Navigation::getItem('/course/opencast');
        $navigation->setImage('../../'.$this->dispatcher->trails_root.'/images/oc-logo-black.png');
        
        $this->set_title(_("Opencast Aufzeichnungen planen"));
        

        $this->course_id = $_SESSION['SessionSeminar'];
        
        $this->cseries = OCModel::getConnectedSeries($this->course_id);
        
        $this->dates  =  OCModel::getDatesForSemester($this->course_id);
        $course = new Seminar($this->course_id);

        $all_semester = SemesterData::GetSemesterArray();

        $this->course_semester = array();
        
        foreach($all_semester as $cur_semester) {

            //fix for unbegrenzte kurse add marker for current_semester
            if($cur_semester['beginn'] == $course->getStartSemester() || $cur_semester['beginn'] == $course->getEndSemester()) {
                $this->course_semester[] = $cur_semester;
            }

        }


        $this->caa_client = CaptureAgentAdminClient::getInstance();


        $search_client = SearchClient::getInstance();
         
        $this->workflow_client = WorkflowClient::getInstance();
        $this->tagged_wfs = $this->workflow_client->getTaggedWorkflowDefinitions();
    }


    function schedule_action($resource_id, $termin_id)
    {

        $this->course_id = Request::get('cid');
        if($GLOBALS['perm']->have_studip_perm('dozent', $this->course_id)){
            $scheduler_client = SchedulerClient::getInstance();
            if($scheduler_client->scheduleEventForSeminar($this->course_id, $resource_id, $termin_id)) {
                $this->flash['messages'] = array('success'=> _("Aufzeichnung wurde geplant."));
                log_event('OC_SCHEDULE_EVENT', $termin_id, $this->course_id);
            } else {
                $this->flash['messages'] = array('error'=> _("Aufzeichnung konnte nicht geplant werden."));
            }
        } else {
            throw new Exception(_("Sie haben leider keine Berechtigungen um diese Aktion durchzuführen"));
        }
        $this->redirect(PluginEngine::getLink('opencast/course/scheduler'));
    }

    function unschedule_action($resource_id, $termin_id)
    {

        $this->course_id = Request::get('cid');
        if($GLOBALS['perm']->have_studip_perm('dozent', $this->course_id)){
            $scheduler_client = SchedulerClient::getInstance();
            if( $scheduler_client->deleteEventForSeminar($this->course_id, $resource_id, $termin_id)) {
                $this->flash['messages'] = array('success'=> _("Die geplante Aufzeichnung wurde entfernt"));
                log_event('OC_CANCEL_SCHEDULED_EVENT', $termin_id, $this->course_id);
            } else {
                $this->flash['messages'] = array('error'=> _("Die geplante Aufzeichnung konnte nicht entfernt werden."));
            }
        } else {
            throw new Exception(_("Sie haben leider keine Berechtigungen um diese Aktion durchzuführen"));
        }

        $this->redirect(PluginEngine::getLink('opencast/course/scheduler'));
    }


    function update_action($resource_id, $termin_id)
    {

        $course_id = Request::get('cid');
        if($GLOBALS['perm']->have_studip_perm('dozent', $course_id)){
            $scheduler_client = SchedulerClient::getInstance();
            $scheduled = OCModel::checkScheduledRecording($course_id, $resource_id, $termin_id);

            if( $scheduler_client->updateEventForSeminar($course_id, $resource_id, $termin_id, $scheduled['event_id'])) {
                $this->flash['messages'] = array('success'=> _("Die geplante Aufzeichnung aktualisiert"));
                log_event('OC_REFRESH_SCHEDULED_EVENT', $termin_id, $course_id);
            } else {
                $this->flash['messages'] = array('error'=> _("Die geplante Aufzeichnung konnte nicht aktualisiert werden."));
            }
        } else {
            throw new Exception(_("Sie haben leider keine Berechtigungen um diese Aktion durchzuführen"));
        }

        $this->redirect(PluginEngine::getLink('opencast/course/scheduler'));
    }


    function create_series_action()
    {
        if($GLOBALS['perm']->have_studip_perm('dozent', $this->course_id)){
            $this->series_client = SeriesClient::getInstance();
            if($this->series_client->createSeriesForSeminar($this->course_id)) {
                $this->flash['messages']['success'] = _("Series wurde angelegt");
                log_event('OC_CREATE_SERIES', $this->course_id);
                
            } else {
                throw new Exception(_("Verbindung zum Series-Service konnte nicht hergestellt werden."));
            }
        } else {
           throw new Exception(_("Sie haben leider keine Berechtigungen um diese Aktion durchzuführen"));
        }
        $this->redirect(PluginEngine::getLink('opencast/course/index'));
    }

    function toggle_visibility_action($episode_id, $position) {
        $this->course_id = Request::get('cid');
        $this->user_id = $GLOBALS['auth']->auth['uid'];

        if($GLOBALS['perm']->have_studip_perm('admin', $this->course_id)
            || OCModel::checkPermForEpisode($episode_id, $this->user_id))
        {
            $visible = OCModel::getVisibilityForEpisode($this->course_id, $episode_id);
            // if visibilty wasn't set before do so...
            if(!$visible){
                OCModel::setVisibilityForEpisode($this->course_id, $episode_id, 'true', $position);
                $visible['visible'] = 'true';
            }

            if($visible['visible'] == 'true'){
               OCModel::setVisibilityForEpisode($this->course_id, $episode_id, 'false', $position);
               $this->flash['messages'] = array('success'=> _("Episode wurde unsichtbar geschaltet"));
                log_event('OC_CHANGE_EPISODE_VISIBILITY', $episode_id, $this->course_id, 'Episode wurde unsichtbar geschaltet');
            } else {
               OCModel::setVisibilityForEpisode($this->course_id, $episode_id, 'true', $position);
               $this->flash['messages'] = array('success'=> _("Episode wurde sichtbar geschaltet"));
                log_event('OC_CHANGE_EPISODE_VISIBILITY', $episode_id, $this->course_id, 'Episode wurde sichtbar geschaltet');
            }
        } else {
            if (Request::isXhr()) {
                $this->set_status('500');
                $this->render_nothing();
            }
            else throw new Exception(_("Sie haben leider keine Berechtigungen um diese Aktion durchzuführen"));

        }
        if (Request::isXhr()) {
            $this->set_status('201');

            $occourse = new OCCourseModel($this->course_id);
            $all_episodes = $occourse->getEpisodes();

            $this->render_json($all_episodes);

        } else {
            $this->redirect(PluginEngine::getLink('opencast/course/index/' . $episode_id));
        }
    }


    /**
     * @deprecated
     */
    function upload_action()
    {
        //TODO this should only work iff an series is connected!
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


    
    function bulkschedule_action()
    {
        $course_id =  Request::get('cid');
        $action = Request::get('action');
        if($GLOBALS['perm']->have_studip_perm('dozent', $course_id)){
            $dates = Request::getArray('dates');
            foreach($dates as $termin_id => $resource_id){
                switch($action) {
                    case "create":
                        self::schedule($resource_id, $termin_id, $course_id);
                        break;
                    case "update":
                        self::updateschedule($resource_id, $termin_id, $course_id);
                        break;
                    case "delete":
                        self::unschedule($resource_id, $termin_id, $course_id);
                        break;
                }
            }
        } else {
            throw new Exception(_("Sie haben leider keine Berechtigungen um diese Aktion durchzuführen"));
        }

        $this->redirect(PluginEngine::getLink('opencast/course/scheduler'));
    }
    
    static function schedule($resource_id, $termin_id, $course_id) {
        $scheduled = OCModel::checkScheduledRecording($course_id, $resource_id, $termin_id);
        if(!$scheduled) {
            $scheduler_client = SchedulerClient::getInstance();

            if($scheduler_client->scheduleEventForSeminar($course_id, $resource_id, $termin_id)) {
                log_event('OC_SCHEDULE_EVENT', $termin_id, $course_id);
                return true;
            } else {
                // TODO FEEDBACK
            }
        }
    }
    
    static function updateschedule($resource_id, $termin_id, $course_id) {

        $scheduled = OCModel::checkScheduledRecording($course_id, $resource_id, $termin_id);
        if($scheduled){
            $scheduler_client = SchedulerClient::getInstance();
            $scheduler_client->updateEventForSeminar($course_id, $resource_id, $termin_id, $scheduled['event_id']);
            log_event('OC_REFRESH_SCHEDULED_EVENT', $termin_id, $course_id);
        } else {
            self::schedule($resource_id, $termin_id, $course_id);
        }  
    }
    
    static function unschedule($resource_id, $termin_id, $course_id) {
        $scheduled = OCModel::checkScheduledRecording($course_id, $resource_id, $termin_id);
        if($scheduled) {
            $scheduler_client = SchedulerClient::getInstance();

            if( $scheduler_client->deleteEventForSeminar($course_id, $resource_id, $termin_id)) {
                log_event('OC_CANCEL_SCHEDULED_EVENT', $termin_id, $course_id);
                return true;
            } else {
                // TODO FEEDBACK
            }
        }
    }
    
    function remove_failed_action($workflow_id) {
        if(OCModel::removeWorkflowIDforCourse($workflow_id, $this->course_id)){
            $this->flash['messages'] = array('success'=> _("Die hochgeladenen Daten wurden gelöscht."));
        } else {
            $this->flash['messages'] = array('error'=> _("Die hochgeladenen Daten konnten nicht gelöscht werden."));
        }
        $this->redirect(PluginEngine::getLink('opencast/course/index/'));
    }

    function get_player_action($episode_id="", $course_id=""){

        $occourse = new OCCourseModel($course_id);
        $episodes = $occourse->getEpisodes();
        $cand_episode = array();
        foreach($episodes as $e){
            if($e['id'] == $episode_id) {
                $e['author'] = $e['author'] !=''? $e['author'] : 'Keine Angaben vorhanden';
                $e['description'] =$e['description'] !='' ? $e['description']  : 'Keine Beschreibung vorhanden';
                $e['start'] = date("d.m.Y H:m",strtotime($e['start']));
                $cand_episode = $e;
            }
        }



        if (Request::isXhr()) {

            $this->set_status('200');
            $active_id = $episode_id;
            $this->search_client = SearchClient::getInstance();

            if($this->theodul) {
                $embed =  $this->search_client->getBaseURL() ."/engage/theodul/ui/core.html?id=".$active_id . "&mode=embed";
            } else {
                $embed =  $this->search_client->getBaseURL() ."/engage/ui/embed.html?id=".$active_id;
            }
            // check whether server supports ssl
            $embed_headers = @get_headers("https://". $embed);
            if($embed_headers) {
                $embed = "https://". $embed;
            } else {
                //UOL FIX
                $embed = "http://". $embed;
                //$embed = "https://". $embed;
            }
            $perm = $GLOBALS['perm']->have_studip_perm('dozent', $course_id);


            $episode = array('active_id' => $active_id,
                            'course_id' => $course_id,
                            'theodul' => $theodul,
                            'embed' => $embed,
                            'perm' => $perm,
                            'engage_player_url' => $this->search_client->getBaseURL() ."/engage/ui/watch.html?id=".$active_id,
                            'episode_data' => $cand_episode
            );

            $this->render_json($episode);
        } else {
            $this->redirect(PluginEngine::getLink('opencast/course/index/' . $episode_id));
        }
    }

    function refresh_episodes_action($ticket){

        if(check_ticket($ticket) && $GLOBALS['perm']->have_studip_perm('dozent',$this->course_id)){
            $occourse2 = new OCCourseModel($this->course_id);
            $occourse2->getEpisodes(true);
            $this->flash['messages'] = array('success'=> _("Die Episodenliste wurde aktualisiert."));
        }

        $this->redirect(PluginEngine::getLink('opencast/course/index/false'));
    }

    function toggle_tab_visibility_action($ticket){
        if(check_ticket($ticket) && $GLOBALS['perm']->have_studip_perm('dozent',$this->course_id)) {
            $occourse = new OCCourseModel($this->course_id);
            $occourse->toggleSeriesVisibility();
            $visibility = $occourse->getSeriesVisibility();
            $vis = array('visible' => 'sichtbar', 'invisible' => 'ausgeblendet');
            $this->flash['messages'] = array('success'=> sprintf(_("Der Reiter in der Kursnavigation ist jetzt für alle Kursteilnehmer %s."),$vis[$visibility]));
            log_event('OC_CHANGE_TAB_VISIBILITY', $this->course_id, NULL, sprintf(_("Reiter ist %s."),$vis[$visibility]));
        }
        $this->redirect(PluginEngine::getLink('opencast/course/index/false'));
    }

    function setworkflow_action(){

        if(check_ticket(Request::get('ticket')) && $GLOBALS['perm']->have_studip_perm('dozent',$this->course_id)){

            $occcourse = new OCCourseModel($this->course_id);

            if($course_workflow = Request::get('oc_course_workflow')){
                if($occcourse->getWorkflow('schedule')) {
                    $occcourse->updateWorkflow($course_workflow, 'schedule');
                }
                else {
                    $occcourse->setWorkflow($course_workflow,'schedule');
                }
            }
            if($course_uploadworkflow = Request::get('oc_course_uploadworkflow')){
                if($occcourse->getWorkflow('upload')){
                    $occcourse->updateWorkflow($course_uploadworkflow, 'upload');
                }
                else {
                    $occcourse->setWorkflow($course_uploadworkflow, 'upload');
                }
            }

        }

        $this->redirect(PluginEngine::getLink('opencast/course/index/false'));
    }

    function setworkflowforscheduledepisode_action($termin_id, $workflow_id, $resource_id){

        if (Request::isXhr() && $GLOBALS['perm']->have_studip_perm('dozent',$this->course_id)) {
            $occcourse = new OCCourseModel($this->course_id);
            $success =  $occcourse->setWorkflowForDate($termin_id, $workflow_id);
            self::updateschedule($resource_id, $termin_id, $this->course_id);
            $this->render_json(json_encode($success));

        } else {
            $this->render_nothing();
        }

    }


}
?>
