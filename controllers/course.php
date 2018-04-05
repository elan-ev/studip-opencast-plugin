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

require_once $this->trails_root.'/classes/OCRestClient/SearchClient.php';
require_once $this->trails_root.'/classes/OCRestClient/SeriesClient.php';

require_once $this->trails_root.'/classes/OCRestClient/UploadClient.php';
require_once $this->trails_root.'/classes/OCRestClient/IngestClient.php';
require_once $this->trails_root.'/classes/OCRestClient/WorkflowClient.php';
require_once $this->trails_root.'/models/OCModel.php';
require_once $this->trails_root.'/models/OCCourseModel.class.php';

require_once $this->trails_root.'/classes/OCRestClient/SchedulerClient.php';

class CourseController extends OpencastController
{
    /**
     * Constructs the controller and provide translations methods.
     *
     * @param object $dispatcher
     * @see https://stackoverflow.com/a/12583603/982902 if you need to overwrite
     *      the constructor of the controller
     */
    public function __construct($dispatcher)
    {
        parent::__construct($dispatcher);

        $this->plugin = $dispatcher->current_plugin;

        // Localization
        $this->_ = function ($string) use ($dispatcher) {
            return call_user_func_array(
                [$dispatcher->current_plugin, '_'],
                func_get_args()
            );
        };

        $this->_n = function ($string0, $tring1, $n) use ($dispatcher) {
            return call_user_func_array(
                [$dispatcher->current_plugin, '_n'],
                func_get_args()
            );
        };
    }

    /**
     * Intercepts all non-resolvable method calls in order to correctly handle
     * calls to _ and _n.
     *
     * @param string $method
     * @param array  $arguments
     * @return mixed
     * @throws RuntimeException when method is not found
     */
    public function __call($method, $arguments)
    {
        $variables = get_object_vars($this);
        if (isset($variables[$method]) && is_callable($variables[$method])) {
            return call_user_func_array($variables[$method], $arguments);
        }
        throw new RuntimeException("Method {$method} does not exist");
    }

    /**
     * Sets the page title. Page title always includes the course name.
     *
     * @param mixed $title Title of the page (optional)
     */
    private function set_title($title = '')
    {
        $title_parts   = func_get_args();

        if (class_exists('Context')) {
            $title_parts[] = Context::getHeaderLine();
        } else {
            $title_parts[] = $GLOBALS['SessSemName']['header_line'];
        }

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


        if (class_exists('Context')) {
            $this->course_id = Context::getId();
        } else {
            $this->course_id = $GLOBALS['SessionSeminar'];
        }

        // notify on trails action
        $klass = substr(get_called_class(), 0, -10);
        $name = sprintf('oc_course.performed.%s_%s', $klass, $action);
        NotificationCenter::postNotification($name, $this);
        // change this variable iff theodulplayer is active
        $this->theodul = true;


        // set the stream context to ignore ssl erros -> get_headers will not work otherwise
        stream_context_set_default( [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);
    }

    /**
     * This is the default action of this controller.
     */
    function index_action($active_id = 'false', $upload_message = false)
    {
        $this->set_title($this->_("Opencast Player"));
        if($upload_message == 'true') {
            $this->flash['messages'] = array('success' =>$this->_('Die Datei wurden erfolgreich hochgeladen. Je nach Größe der Datei und Auslastung des Opencast Matterhorn-Server kann es einige Zeit in Anspruch nehmen, bis die entsprechende Aufzeichnung in der Liste sichtbar wird.'));
        }

        $reload = false;
        // set layout for index page
        $this->states = false;

        if ($GLOBALS['perm']->have_studip_perm('dozent', $this->course_id)) {
            // Config-Dialog
            $this->connectedSeries = OCSeriesModel::getConnectedSeries($this->course_id, true);
            $this->unconnectedSeries = OCSeriesModel::getUnconnectedSeries($this->course_id, true);
            $this->workflow_client = WorkflowClient::getInstance($this->course_id);
            $workflow_ids = OCModel::getWorkflowIDsforCourse($this->course_id);

            $this->series_metadata = OCSeriesModel::getConnectedSeriesDB($this->course_id);
            if(!empty($workflow_ids)){
                $this->states = OCModel::getWorkflowStates($course_id, $workflow_ids);
            }

            //workflow
            $occourse = new OCCourseModel($this->course_id);
            $this->tagged_wfs = $this->workflow_client->getTaggedWorkflowDefinitions();

            $this->schedulewf = $occourse->getWorkflow('schedule');
            $this->uploadwf = $occourse->getWorkflow('upload');

        }

        Navigation::activateItem('course/opencast/overview');
        try {
                $this->search_client = SearchClient::getInstance($this->course_id);

                $occourse = new OCCourseModel($this->course_id);
                $this->coursevis = $occourse->getSeriesVisibility();

                if ($occourse->getSeriesID()) {

                    $ordered_episode_ids = $occourse->getEpisodes($reload);
                    if (!$GLOBALS['perm']->have_studip_perm('dozent', $this->course_id)) {
                        $this->ordered_episode_ids = $occourse->refineEpisodesForStudents($ordered_episode_ids);
                    } else {
                        $this->ordered_episode_ids = $ordered_episode_ids;
                    }

                    if (empty($active_id) || $active_id != "false") {
                        $this->active_id = $active_id;
                    } else if(isset($this->ordered_episode_ids)){
                        $first = current($this->ordered_episode_ids);
                        $this->active_id = $first['id'];
                    }

                    if (!empty($this->ordered_episode_ids)) {
                        $engage_url =  parse_url($this->search_client->getBaseURL());

                        foreach($this->ordered_episode_ids as $ep) {
                            if($ep['id'] == $this->active_id) {
                                if($ep['prespreview']){
                                    $this->previewimage = $ep['prespreview'];
                                } else {
                                    $this->previewimage = false;
                                }
                            }
                        }


                        if ($this->theodul) {
                            $this->embed =  $this->search_client->getBaseURL() ."/engage/theodul/ui/core.html?id=".$this->active_id . "&mode=embed";
                        } else {
                            $this->embed =  $this->search_client->getBaseURL() ."/engage/ui/embed.html?id=".$this->active_id;
                        }

                        $this->engage_player_url = $this->search_client->getBaseURL() ."/engage/ui/watch.html?id=".$this->active_id;
                    }

                    // Upload-Dialog
                    $this->date = date('Y-m-d');
                    $this->hour = date('H');
                    $this->minute = date('i');

                    //check needed services before showing upload form
                    UploadClient::getInstance($this->course_id)->checkService();
                    IngestClient::getInstance($this->course_id)->checkService();
                    SeriesClient::getInstance($this->course_id)->checkService();



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
        $navigation->setImage(new Icon('../../'.$this->dispatcher->trails_root.'/images/oc-logo-black.png'));

        $this->set_title($this->_("Opencast Konfiguration"));


        $this->connectedSeries = OCSeriesModel::getConnectedSeries($this->course_id);
        $this->unconnectedSeries = OCSeriesModel::getUnconnectedSeries($this->course_id, true);

    }

    function edit_action($course_id)
    {

        $series = Request::getArray('series');
        foreach( $series as $serie) {
            OCSeriesModel::setSeriesforCourse($course_id, $serie, 'visible', 0, time());
            StudipLog::log('OC_CONNECT_SERIES',$serie, $course_id);
        }
        $this->flash['messages'] = array('success'=> $this->_("Änderungen wurden erfolgreich übernommen. Es wurde eine Serie für den Kurs verknüpft."));

        $this->redirect('course/index');
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
            $series_client = SeriesClient::getInstance($this->course_id);
            $series_client->removeSeries($series_id);
            */
            $this->flash['messages'] = array('success'=> $this->_("Die Zuordnung wurde entfernt"));

            StudipLog::log('OC_REMOVE_CONNECTED_SERIES',$series_id, $course_id);
        }
        else{
            $this->flash['messages']['error'] = $this->_("Die Zuordnung konnte nicht entfernt werden.");
        }

        $this->flash['cand_delete'] = true;

        $this->redirect('course/index');
    }


    function scheduler_action()
    {
        require_once 'lib/raumzeit/raumzeit_functions.inc.php';
        Navigation::activateItem('course/opencast/scheduler');
        $navigation = Navigation::getItem('/course/opencast');
        $navigation->setImage(new Icon('../../'.$this->dispatcher->trails_root.'/images/oc-logo-black.png'));

        $this->set_title($this->_("Opencast Aufzeichnungen planen"));

        $this->cseries = OCModel::getConnectedSeries($this->course_id);

        $course = new Seminar($this->course_id);

        $start_semester = $course->getStartSemester();
        $end_semester   = $course->getEndSemester();

        if ($start_semester > time() || $end_semester == 0) {
            $semester = Semester::findByTimestamp($start_semester);
        } else if ($end_semester < time() && $end_semester > 0) {
            $semester = Semester::findByTimestamp($end_semester);
        } else {
            $semester = Semester::findCurrent();
        }
        $this->dates  =  OCModel::getDatesForSemester($this->course_id, $semester);

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

        $this->workflow_client = WorkflowClient::getInstance($this->course_id);
        $this->tagged_wfs = $this->workflow_client->getTaggedWorkflowDefinitions();
    }


    function schedule_action($resource_id, $termin_id)
    {
        if($GLOBALS['perm']->have_studip_perm('dozent', $this->course_id)){
            $scheduler_client = SchedulerClient::getInstance($this->course_id);
            if ($scheduler_client->scheduleEventForSeminar($this->course_id, $resource_id, $termin_id)) {
                $this->flash['messages'] = array('success'=> $this->_("Aufzeichnung wurde geplant."));

                $course = Course::find($this->course_id);
                $members = $course->members;

                $users = array();
                foreach($members as $member){
                    $users[] = $member->user_id;
                }

                $notification =  sprintf($this->_('Die Veranstaltung "%s" wird für Sie mit Bild und Ton automatisiert aufgezeichnet.'), $course->name);
                PersonalNotifications::add(
                    $users, PluginEngine::getLink('opencast/course/index', array('cid' => $this->course_id)),
                    $notification, $this->course_id,
                    Icon::create($this->plugin->getPluginUrl(). '/images/newocicon.png')
                );

                StudipLog::log('OC_SCHEDULE_EVENT', $termin_id, $this->course_id);
            } else {
                $this->flash['messages'] = array('error'=> $this->_("Aufzeichnung konnte nicht geplant werden."));
            }
        } else {
            throw new Exception($this->_("Sie haben leider keine Berechtigungen um diese Aktion durchzuführen"));
        }
        $this->redirect('course/scheduler');
    }

    function unschedule_action($resource_id, $termin_id)
    {

        $this->course_id = Request::get('cid');
        if ($GLOBALS['perm']->have_studip_perm('dozent', $this->course_id)) {
            $scheduler_client = SchedulerClient::getInstance($this->course_id);
            if ($scheduler_client->deleteEventForSeminar($this->course_id, $resource_id, $termin_id)) {
                $this->flash['messages'] = array('success'=> $this->_("Die geplante Aufzeichnung wurde entfernt"));
                StudipLog::log('OC_CANCEL_SCHEDULED_EVENT', $termin_id, $this->course_id);
            } else {
                $this->flash['messages'] = array('error'=> $this->_("Die geplante Aufzeichnung konnte nicht entfernt werden."));
            }
        } else {
            throw new Exception($this->_("Sie haben leider keine Berechtigungen um diese Aktion durchzuführen"));
        }

        $this->redirect('course/scheduler');
    }


    function update_action($resource_id, $termin_id)
    {

        $course_id = Request::get('cid');
        if($GLOBALS['perm']->have_studip_perm('dozent', $course_id)){
            $scheduler_client = SchedulerClient::getInstance($this->course_id);
            $scheduled = OCModel::checkScheduledRecording($course_id, $resource_id, $termin_id);

            if( $scheduler_client->updateEventForSeminar($course_id, $resource_id, $termin_id, $scheduled['event_id'])) {
                $this->flash['messages'] = array('success'=> $this->_("Die geplante Aufzeichnung aktualisiert"));
                StudipLog::log('OC_REFRESH_SCHEDULED_EVENT', $termin_id, $course_id);
            } else {
                $this->flash['messages'] = array('error'=> $this->_("Die geplante Aufzeichnung konnte nicht aktualisiert werden."));
            }
        } else {
            throw new Exception($this->_("Sie haben leider keine Berechtigungen um diese Aktion durchzuführen"));
        }

        $this->redirect('course/scheduler');
    }


    function create_series_action()
    {
        if($GLOBALS['perm']->have_studip_perm('dozent', $this->course_id)){
            $this->series_client = SeriesClient::getInstance($this->course_id);
            if ($this->series_client->createSeriesForSeminar($this->course_id)) {
                $this->flash['messages']['success'] = $this->_("Series wurde angelegt");
                StudipLog::log('OC_CREATE_SERIES', $this->course_id);

            } else {
                throw new Exception($this->_("Verbindung zum Series-Service konnte nicht hergestellt werden."));
            }
        } else {
           throw new Exception($this->_("Sie haben leider keine Berechtigungen um diese Aktion durchzuführen"));
        }
        $this->redirect('course/index');
    }

    function toggle_visibility_action($episode_id, $position)
    {
        $this->course_id = Request::get('cid');
        $this->user_id   = $GLOBALS['auth']->auth['uid'];

        if ($GLOBALS['perm']->have_studip_perm('admin', $this->course_id)
            || OCModel::checkPermForEpisode($episode_id, $this->user_id))
        {
            $visible = OCModel::getVisibilityForEpisode($this->course_id, $episode_id);
            // if visibilty wasn't set before do so...
            if (!$visible) {
                OCModel::setVisibilityForEpisode($this->course_id, $episode_id, 'true', $position);
                $visible['visible'] = 'true';
            }

            if ($visible['visible'] == 'true') {
                OCModel::setVisibilityForEpisode($this->course_id, $episode_id, 'false', $position);

                if (!Request::isXhr()) {
                    $this->flash['messages'] = array('success'=> $this->_("Episode wurde unsichtbar geschaltet"));
                }

                StudipLog::log('OC_CHANGE_EPISODE_VISIBILITY', $episode_id, $this->course_id, 'Episode wurde unsichtbar geschaltet');
            } else {
                OCModel::setVisibilityForEpisode($this->course_id, $episode_id, 'true', $position);

                if (!Request::isXhr()) {
                    $this->flash['messages'] = array('success'=> $this->_("Episode wurde sichtbar geschaltet"));
                }

                StudipLog::log('OC_CHANGE_EPISODE_VISIBILITY', $episode_id, $this->course_id, 'Episode wurde sichtbar geschaltet');
            }
        } else {

            if (Request::isXhr()) {
                $this->set_status('500');
                $this->render_nothing();
            } else {
                throw new Exception($this->_("Sie haben leider keine Berechtigungen um diese Aktion durchzuführen"));
            }
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
            UploadClient::getInstance($this->course_id)->checkService();
            IngestClient::getInstance($this->course_id)->checkService();
            MediaPackageClient::getInstance($this->course_id)->checkService();
            SeriesClient::getInstance($this->course_id)->checkService();

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
            throw new Exception($this->_("Sie haben leider keine Berechtigungen um diese Aktion durchzuführen"));
        }

        $this->redirect('course/scheduler');
    }

    static function schedule($resource_id, $termin_id, $course_id) {
        $scheduled = OCModel::checkScheduledRecording($course_id, $resource_id, $termin_id);
        if(!$scheduled) {
            $scheduler_client = SchedulerClient::getInstance($course_id);

            if($scheduler_client->scheduleEventForSeminar($course_id, $resource_id, $termin_id)) {
                StudipLog::log('OC_SCHEDULE_EVENT', $termin_id, $course_id);
                return true;
            } else {
                // TODO FEEDBACK
            }
        }
    }

    static function updateschedule($resource_id, $termin_id, $course_id) {

        $scheduled = OCModel::checkScheduledRecording($course_id, $resource_id, $termin_id);
        if($scheduled){
            $scheduler_client = SchedulerClient::getInstance($course_id);
            $scheduler_client->updateEventForSeminar($course_id, $resource_id, $termin_id, $scheduled['event_id']);
            StudipLog::log('OC_REFRESH_SCHEDULED_EVENT', $termin_id, $course_id);
        } else {
            self::schedule($resource_id, $termin_id, $course_id);
        }
    }

    static function unschedule($resource_id, $termin_id, $course_id) {
        $scheduled = OCModel::checkScheduledRecording($course_id, $resource_id, $termin_id);
        if($scheduled) {
            $scheduler_client = SchedulerClient::getInstance($course_id);

            if( $scheduler_client->deleteEventForSeminar($course_id, $resource_id, $termin_id)) {
                StudipLog::log('OC_CANCEL_SCHEDULED_EVENT', $termin_id, $course_id);
                return true;
            } else {
                // TODO FEEDBACK
            }
        }
    }

    function remove_failed_action($workflow_id) {
        if(OCModel::removeWorkflowIDforCourse($workflow_id, $this->course_id)){
            $this->flash['messages'] = array('success'=> $this->_("Die hochgeladenen Daten wurden gelöscht."));
        } else {
            $this->flash['messages'] = array('error'=> $this->_("Die hochgeladenen Daten konnten nicht gelöscht werden."));
        }
        $this->redirect('course/index/');
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
            $this->search_client = SearchClient::getInstance($this->course_id);

            if($this->theodul) {
                $embed =  $this->search_client->getBaseURL() ."/engage/theodul/ui/core.html?id=".$active_id . "&mode=embed";
            } else {
                $embed =  $this->search_client->getBaseURL() ."/engage/ui/embed.html?id=".$active_id;
            }

            $perm = $GLOBALS['perm']->have_studip_perm('dozent', $course_id);


            $episode = array(
                'active_id'         => $active_id,
                'course_id'         => $course_id,
                'theodul'           => $theodul,
                'embed'             => $embed,
                'perm'              => $perm,
                'engage_player_url' => $this->search_client->getBaseURL() ."/engage/ui/watch.html?id=".$active_id,
                'episode_data'      => $cand_episode
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

            $this->flash['messages'] = array('success'=> $this->_("Die Episodenliste wurde aktualisiert."));
        }

        $this->redirect('course/index/false');
    }

    function refresh_sorting_action($ticket)
    {
        if(check_ticket($ticket) && $GLOBALS['perm']->have_studip_perm('dozent',$this->course_id)){
            $occourse2 = new OCCourseModel($this->course_id);
            $occourse2->clearSeminarEpisodes();

            $this->flash['messages'] = array('success'=> $this->_("Die Sortierung wurde zurückgesetzt."));
        }

        $this->redirect('course/index/false');
    }

    function toggle_tab_visibility_action($ticket){
        if(check_ticket($ticket) && $GLOBALS['perm']->have_studip_perm('dozent',$this->course_id)) {
            $occourse = new OCCourseModel($this->course_id);
            $occourse->toggleSeriesVisibility();
            $visibility = $occourse->getSeriesVisibility();
            $vis = array('visible' => 'sichtbar', 'invisible' => 'ausgeblendet');
            $this->flash['messages'] = array('success'=> sprintf($this->_("Der Reiter in der Kursnavigation ist jetzt für alle Kursteilnehmer %s."),$vis[$visibility]));
            StudipLog::log('OC_CHANGE_TAB_VISIBILITY', $this->course_id, NULL, sprintf($this->_("Reiter ist %s."),$vis[$visibility]));
        }
        $this->redirect('course/index/false');
    }

    function workflow_action()
    {
        if (Request::isXhr()) {
            $this->set_layout(null);
        }

        $this->workflow_client = WorkflowClient::getInstance($this->course_id);
        $this->workflows = $this->workflow_client->getTaggedWorkflowDefinitions();

        $occourse = new OCCourseModel($this->course_id);
        $this->uploadwf = $occourse->getWorkflow('upload');
    }

    function setworkflow_action()
    {
        if (check_ticket(Request::get('ticket'))
            && $GLOBALS['perm']->have_studip_perm('dozent', $this->course_id)
        ) {

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

        $this->redirect('course/index/false');
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
