<?php
/*
 * course.php - course controller
 */

use Opencast\Models\OCConfig;
use Opencast\Models\OCSeminarSeries;
use Opencast\Models\OCTos;
use Opencast\Models\OCScheduledRecordings;
use Opencast\LTI\OpencastLTI;

class CourseController extends OpencastController
{
    /**
     * Constructs the controller and provide translations methods.
     *
     * @param object $dispatcher
     *
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

        PageLayout::setHelpKeyword('Opencast');
    }

    /**
     * Intercepts all non-resolvable method calls in order to correctly handle
     * calls to _ and _n.
     *
     * @param string $method
     * @param array  $arguments
     *
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
        $title_parts = func_get_args();

        if (class_exists('Context')) {
            $title_parts[] = Context::getHeaderLine();
        } else {
            $title_parts[] = $GLOBALS['SessSemName']['header_line'];
        }

        $title_parts = array_reverse($title_parts);
        $page_title = implode(' - ', $title_parts);


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


        if (class_exists('Context')) {
            $this->course_id = Context::getId();
        } else {
            $this->course_id = $GLOBALS['SessionSeminar'];
        }

        // notify on trails action
        $klass = substr(get_called_class(), 0, -10);
        $name = sprintf('oc_course.performed.%s_%s', $klass, $action);
        NotificationCenter::postNotification($name, $this);

        $this->config = OCConfig::getConfigForCourse($this->course_id);

        $this->paella = $this->config['paella'] == '0' ? false : true;

        // set the stream context to ignore ssl erros -> get_headers will not work otherwise
        stream_context_set_default([
            'ssl' => [
                'verify_peer'      => false,
                'verify_peer_name' => false,
            ],
        ]);

        // check, if current user is lecturer, force tos if so
        if (Config::get()->OPENCAST_SHOW_TOS
            && !$GLOBALS['perm']->have_studip_perm('admin', $this->course_id)
            && $action != 'tos' && $action != 'access_denied' && $action != 'accept_tos') {
            if ($GLOBALS['perm']->have_studip_perm('dozent', $this->course_id)) {
                if (empty(OCTos::findBySQL('user_id = ? AND seminar_id = ?', [
                    $GLOBALS['user']->id, $this->course_id
                ]))) {
                    $this->redirect('course/tos');
                }
            } else {
                if (empty(OCTos::findBySQL('seminar_id = ?', [
                    $this->course_id
                ]))) {
                    $this->redirect('course/access_denied');
                }
            }
        }
    }

    /**
     * This is the default action of this controller.
     */
    function index_action($active_id = 'false', $upload_message = false)
    {
        global $perm;

        $this->set_title($this->_("Opencast Player"));

        if ($upload_message == 'true') {
            $this->flash['messages'] = ['success' => $this->_('Die Datei wurde erfolgreich hochgeladen. Je nach Größe der Datei und Auslastung des Opencast-Servers kann es einige Zeit dauern, bis die Aufzeichnung in der Liste sichtbar wird.')];
        }

        $reload = true;
        $this->states = false;


        foreach (OCSeminarSeries::getMissingSeries($this->course_id) as $series) {
            PageLayout::postError(sprintf($this->_(
                'Die verknüpfte Serie mit der ID "%s" konnte nicht in Opencast gefunden werden! ' .
                'Verküpfen sie bitte eine andere Serie, erstellen Sie eine neue oder ' .
                'wenden Sie sich an einen Systemadministrator.'
            ), $series['series_id']));
        }

        $this->connectedSeries = OCSeminarSeries::getSeries($this->course_id);

        if (
            $GLOBALS['perm']->have_studip_perm('tutor', $this->course_id)
            && !empty($this->connectedSeries)
        ) {
            // Config-Dialog

            foreach ($this->connectedSeries as $key => $series) {

                if ($series['schedule']) {
                    $this->can_schedule = true;
                }

                $oc_series = OCSeriesModel::getSeriesFromOpencast($series);
                $this->connectedSeries[$key] = array_merge($series->toArray(), $oc_series);
            }

            if ($perm->have_perm('root')) {
                $this->workflow_client = WorkflowClient::getInstance();
                $workflow_ids = OCModel::getWorkflowIDsforCourse($this->course_id);
                if (!empty($workflow_ids)) {
                    $this->states = OCModel::getWorkflowStates($this->course_id, $workflow_ids);
                }
                //workflow
                $occourse = new OCCourseModel($this->course_id);
                $this->tagged_wfs = $this->workflow_client->getTaggedWorkflowDefinitions();

                $this->schedulewf = $occourse->getWorkflow('schedule');
                $this->uploadwf = $occourse->getWorkflow('upload');
            }
        }

        if (!empty($this->connectedSeries)) {
            if (Config::get()->OPENCAST_HIDE_EPISODES) {
                OpencastLTI::setAcls($this->course_id);
            }
            OpencastLTI::updateEpisodeVisibility($this->course_id);
        }

        Navigation::activateItem('course/opencast/overview');
        try {
            $this->search_client = SearchClient::getInstance();

            $occourse = new OCCourseModel($this->course_id);

            $this->coursevis = $occourse->getSeriesVisibility();

            if ($occourse->getSeriesID()) {

                $this->ordered_episode_ids = $this->get_ordered_episode_ids($reload);

                if (!empty($this->ordered_episode_ids)) {

                    if ($this->paella) {
                        $this->video_url = $this->search_client->getBaseURL() . "/paella/ui/watch.html?id=";
                    } else {
                        $this->video_url = $this->search_client->getBaseURL() . "/engage/theodul/ui/core.html?id=";
                    }
                }

                // Upload-Dialog
                $this->date = date('Y-m-d');
                $this->hour = date('H');
                $this->minute = date('i');

                // Remove Series
                if ($this->flash['cand_delete']) {
                    $this->flash['delete'] = true;
                }
            } else {

            }
        } catch (Exception $e) {
            $this->flash['error'] = $e->getMessage();
            $this->render_action('_error');
        }

        $this->configs = OCConfig::getBaseServerConf();
    }

    private function get_ordered_episode_ids($reload, $minimum_full_view_perm = 'tutor')
    {
        try {
            $oc_course = new OCCourseModel($this->course_id);
            if ($oc_course->getSeriesID()) {
                $ordered_episode_ids = $oc_course->getEpisodes($reload);

                if (!$GLOBALS['perm']->have_studip_perm($minimum_full_view_perm, $this->course_id)) {
                    $ordered_episode_ids = $oc_course->refineEpisodesForStudents($ordered_episode_ids);
                }
            }

            return $ordered_episode_ids;
        } catch (Exception $e) {
            return false;
        }
    }

    function tos_action()
    {
        if (!Config::get()->OPENCAST_SHOW_TOS
            || !$GLOBALS['perm']->have_studip_perm('dozent', $this->course_id)
        ) {
            return $this->redirect('course/index');
        }

        $this->set_title($this->_("Opencast - Datenschutzrichtlinien"));
        Navigation::activateItem('course/opencast');

        $this->config = OCConfig::find(1);
    }

    function accept_tos_action()
    {
        if (!Config::get()->OPENCAST_SHOW_TOS
            || !$GLOBALS['perm']->have_studip_perm('dozent', $this->course_id)
        ) {
            return $this->redirect('course/index');
        }

        if (empty(OCTos::findBySQL('user_id = ? AND seminar_id = ?', [
            $GLOBALS['user']->id, $this->course_id
        ]))) {
            $tos = new OCTos();

            $tos->setData([
                'user_id'    => $GLOBALS['user']->id,
                'seminar_id' => $this->course_id
            ]);

            $tos->store();
        }

        $this->redirect('course/index');
    }

    function withdraw_tos_action()
    {
        if (!Config::get()->OPENCAST_SHOW_TOS
            || !$GLOBALS['perm']->have_studip_perm('dozent', $this->course_id)
        ) {
            return $this->redirect('course/index');
        }

        OCTos::deleteBySQL('seminar_id = ?', [
            $this->course_id
        ]);

        $this->redirect('course/index');
    }

    function access_denied_action()
    {
        if (!Config::get()->OPENCAST_SHOW_TOS) {
            return $this->redirect('course/index');
        }

        $this->set_title($this->_("Opencast - Zugriff verweigert"));
        Navigation::activateItem('course/opencast');
    }

    function config_action()
    {
        if (!$GLOBALS['perm']->have_perm('root')) {
            throw new AccessDeniedException();
        }

        if (Request::isXhr()) {
            $this->set_layout(null);
        }

        if (isset($this->flash['messages'])) {
            $this->message = $this->flash['messages'];
        }

        Navigation::activateItem('course/opencast');
        $navigation = Navigation::getItem('/course/opencast');
        $navigation->setImage(new Icon('../../' . $this->dispatcher->trails_root . '/images/opencast-black.svg'));

        $this->set_title($this->_("Opencast Konfiguration"));
        $this->response->add_header('X-Title', rawurlencode($this->_("Series mit Veranstaltung verknüpfen")));


        $this->configs = OCConfig::getBaseServerConf();

        foreach ($this->configs as $id => $config) {
            $sclient = SearchClient::getInstance($id);
            if ($series = $sclient->getAllSeries($this->course_id)) {
                $this->all_series[$id] = $series;
            }
        }
    }

    function edit_action($course_id)
    {
        $series = json_decode(Request::get('series'), true);

        OCSeriesModel::setSeriesforCourse($course_id, $series['config_id'],
            $series['series_id'], 'visible', 0, time());
        StudipLog::log('OC_CONNECT_SERIES', null, $course_id, $serie);

        $this->flash['messages'] = ['success' => $this->_("Änderungen wurden erfolgreich übernommen. Es wurde eine Serie für den Kurs verknüpft.")];

        $this->redirect('course/index');
    }

    function remove_series_action($ticket)
    {
        if (Request::submitted('cancel')) {
            $this->redirect('course/index');

            return;
        }

        OCPerm::check('tutor');

        $course_id = Context::getId();

        if (Request::submitted('delete') && check_ticket($ticket)) {
            $scheduled_episodes = OCSeriesModel::getScheduledEpisodes($course_id);

            OCSeriesModel::removeSeriesforCourse($course_id);

            $this->flash['messages'] = ['success' => $this->_("Die Zuordnung wurde entfernt")];

            StudipLog::log('OC_REMOVE_CONNECTED_SERIES', null, $course_id, '');
        } else {
            $this->flash['messages']['error'] = $this->_("Die Zuordnung konnte nicht entfernt werden.");
        }

        $this->flash['cand_delete'] = true;

        $this->redirect('course/index');
    }


    function scheduler_action()
    {
        Navigation::activateItem('course/opencast/scheduler');
        $navigation = Navigation::getItem('/course/opencast');
        $navigation->setImage(new Icon('../../' . $this->dispatcher->trails_root . '/images/opencast-black.svg'));

        $this->set_title($this->_("Opencast Aufzeichnungen planen"));

        $this->cseries = OCSeminarSeries::getSeries($this->course_id);

        $course = new Seminar($this->course_id);

        $selectable_semesters = new SimpleCollection(Semester::getAll());
        $start                = $course->start_time;
        $end                  = $course->duration_time == -1 ? PHP_INT_MAX : $course->end_time;
        $selectable_semesters = $selectable_semesters->findBy('beginn', [$start, $end], '>=<=')->toArray();
        if (count($selectable_semesters) > 1 || (count($selectable_semesters) == 1 && $course->hasDatesOutOfDuration())) {
            $selectable_semesters[] = ['name' => _('Alle Semester'), 'semester_id' => 'all'];
        }

        $this->selectable_semesters = array_reverse($selectable_semesters);

        $current_semester = reset($this->selectable_semesters);
        $this->semester_filter  = Request::option('semester_filter') ?: $current_semester['semester_id'];
        UrlHelper::bindLinkParam('semester_filter', $this->semester_filter);

        $this->dates = OCModel::getDatesForSemester($this->course_id, $this->semester_filter);

        $this->all_semester = Semester::getAll();

        $this->caa_client      = CaptureAgentAdminClient::getInstance();

        $this->workflow_client = WorkflowClient::getInstance();
        $this->tagged_wfs      = $this->workflow_client->getTaggedWorkflowDefinitions();
    }


    function schedule_action($resource_id, $termin_id)
    {
        if ($GLOBALS['perm']->have_studip_perm('dozent', $this->course_id)) {
            $scheduler_client = SchedulerClient::getInstance();
            if ($scheduler_client->scheduleEventForSeminar($this->course_id, $resource_id, $termin_id)) {
                $this->flash['messages'] = ['success' => $this->_("Aufzeichnung wurde geplant.")];

                $course = Course::find($this->course_id);
                $members = $course->members;

                $users = [];
                foreach ($members as $member) {
                    $users[] = $member->user_id;
                }

                $notification = sprintf($this->_('Die Veranstaltung "%s" wird für Sie mit Bild und Ton automatisiert aufgezeichnet.'), $course->name);
                PersonalNotifications::add(
                    $users, PluginEngine::getLink('opencast/course/index', ['cid' => $this->course_id]),
                    $notification, $this->course_id,
                    Icon::create($this->plugin->getPluginUrl() . '/images/opencast-black.svg')
                );

                StudipLog::log('OC_SCHEDULE_EVENT', $termin_id, $this->course_id);
            } else {
                $this->flash['messages'] = ['error' => $this->_("Aufzeichnung konnte nicht geplant werden.")];
            }
        } else {
            throw new Exception($this->_("Sie haben leider keine Berechtigungen um diese Aktion durchzuführen"));
        }
        $this->redirect('course/scheduler?semester_filter=' . Request::option('semester_filter'));
    }

    function unschedule_action($resource_id, $termin_id)
    {

        $this->course_id = Request::get('cid');
        if ($GLOBALS['perm']->have_studip_perm('dozent', $this->course_id)) {
            $scheduler_client = SchedulerClient::getInstance();
            if ($scheduler_client->deleteEventForSeminar($this->course_id, $resource_id, $termin_id)) {
                $this->flash['messages'] = ['success' => $this->_("Die geplante Aufzeichnung wurde entfernt")];
                StudipLog::log('OC_CANCEL_SCHEDULED_EVENT', $termin_id, $this->course_id);
            } else {
                $this->flash['messages'] = ['error' => $this->_("Die geplante Aufzeichnung konnte nicht entfernt werden.")];
            }
        } else {
            throw new Exception($this->_("Sie haben leider keine Berechtigungen um diese Aktion durchzuführen"));
        }

        $this->redirect('course/scheduler?semester_filter=' . Request::option('semester_filter'));
    }

    function schedule_update_action()
    {
        $event_id = Request::get('event_id');
        $event    = OCScheduledRecordings::find($event_id);

        if ($event && Config::get()->OPENCAST_ALLOW_ALTERNATE_SCHEDULE
            && $GLOBALS['perm']->have_studip_perm('tutor', $event->seminar_id)
        ) {

            $start = Request::get('start');
            $end   = Request::get('end');

            if ($event) {
                $date = $event->date->date;

                $new_start = mktime(
                    floor($start / 60),
                    $start - floor($start / 60) * 60,
                    0,
                    date('n', $date),
                    date('j', $date),
                    date('Y', $date)
                );

                $new_end = mktime(
                    floor($end / 60),
                    $end - floor($end / 60) * 60,
                    0,
                    date('n', $date),
                    date('j', $date),
                    date('Y', $date)
                );

                $event->start = $new_start;
                $event->end   = $new_end;
                $event->store();

                // update event in opencast
                $scheduler_client = SchedulerClient::create($event->seminar_id);
                $scheduler_client->updateEventForSeminar($event->seminar_id, $event->resource_id, $event->date_id, $event->event_id);
            }
        }

        $this->render_nothing();
    }


    function update_action($resource_id, $termin_id)
    {

        $course_id = Request::get('cid');
        if ($GLOBALS['perm']->have_studip_perm('tutor', $course_id)) {
            $scheduler_client = SchedulerClient::create($course_id);
            $scheduled = OCModel::checkScheduledRecording($course_id, $resource_id, $termin_id);

            if ($scheduler_client->updateEventForSeminar($course_id, $resource_id, $termin_id, $scheduled['event_id'])) {
                $this->flash['messages'] = ['success' => $this->_("Die geplante Aufzeichnung wurde aktualisiert.")];
                StudipLog::log('OC_REFRESH_SCHEDULED_EVENT', $termin_id, $course_id);
            } else {
                $this->flash['messages'] = ['error' => $this->_("Die geplante Aufzeichnung konnte nicht aktualisiert werden.")];
            }
        } else {
            throw new Exception($this->_("Sie haben leider keine Berechtigungen um diese Aktion durchzuführen"));
        }

        $this->redirect('course/scheduler?semester_filter=' . Request::option('semester_filter'));
    }


    function create_series_action()
    {
        if ($GLOBALS['perm']->have_studip_perm('tutor', $this->course_id)) {
            $this->series_client = SeriesClient::create($this->course_id);
            if ($this->series_client->createSeriesForSeminar($this->course_id)) {
                $this->flash['messages']['success'] = $this->_("Series wurde angelegt");
                StudipLog::log('OC_CREATE_SERIES', $this->course_id);
                StudipCacheFactory::getCache()->expire('oc_allseries');
            } else {
                throw new Exception($this->_("Verbindung zum Series-Service konnte nicht hergestellt werden."));
            }
        } else {
            throw new Exception($this->_("Sie haben leider keine Berechtigungen um diese Aktion durchzuführen"));
        }
        $this->redirect('course/index');
    }

    /**
     * Set the view permissions for the passed episode
     *
     * @param  [type] $episode_id [description]
     * @param  [type] $permission [description]
     *
     * @return [type]             [description]
     */
    function permission_action($episode_id, $permission)
    {
        $this->course_id = Request::get('cid');
        $this->user_id = $GLOBALS['auth']->auth['uid'];
        $success = true;

        if ($GLOBALS['perm']->have_studip_perm('admin', $this->course_id)
            || OCModel::checkPermForEpisode($episode_id, $this->user_id))
        {
            if (OCModel::setVisibilityForEpisode($this->course_id, $episode_id, $permission)) {
                StudipLog::log('OC_CHANGE_EPISODE_VISIBILITY', null, $this->course_id, "Episodensichtbarkeit wurde auf $permission geschaltet ($episode_id)");
                $this->set_status('201');
            } else {
                // republishing failed, report error to frontend
                $this->set_status('409');
            }

            $this->render_json(OCModel::getEntry($this->course_id, $episode_id)->toArray());
        } else {
            throw new AccessDeniedException();
        }
    }

    /**
     * @deprecated
     */
    function upload_action()
    {
        $this->connectedSeries = OCSeminarSeries::getSeries($this->course_id);

        if (!$this->connectedSeries) {
            throw new Exception('Es ist keine Serie mit dieser Veranstaltung verknüpft!');
        }

        $this->set_title($this->_("Opencast Medienupload"));

        $workflow_client = WorkflowClient::getInstance();

        $workflows = array_filter(
            $workflow_client->getTaggedWorkflowDefinitions(),
            function ($element) {
                return (in_array('schedule', $element['tags']) !== false
                    || in_array('schedule-ng', $element['tags']) !== false)
                    ? $element
                    : false;
            }
        );

        $occourse = new OCCourseModel($this->course_id);
        $this->workflow = $occourse->getWorkflow('upload');

        if ($this->workflow) {
            foreach ($workflows as $wf) {
                if ($wf['id'] == $this->workflow['workflow_id']) {
                    $this->workflow_text = $wf['title'];
                }
            }
        }

        if (Request::isXhr()) {
            $this->set_layout(null);
        } else {
            Navigation::activateItem('course/opencast/overview');
        }
    }


    function bulkschedule_action()
    {
        $course_id = Context::getId();
        $action = Request::get('action');
        if ($GLOBALS['perm']->have_studip_perm('dozent', $course_id)) {
            $dates = Request::getArray('dates');
            foreach ($dates as $termin_id => $resource_id) {
                switch ($action) {
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

        $this->redirect('course/scheduler?semester_filter=' . Request::option('semester_filter'));
    }

    static function schedule($resource_id, $termin_id, $course_id)
    {
        $scheduled = OCModel::checkScheduledRecording($course_id, $resource_id, $termin_id);
        if (!$scheduled) {
            $scheduler_client = SchedulerClient::getInstance(OCConfig::getConfigIdForCourse($course_id));

            if ($scheduler_client->scheduleEventForSeminar($course_id, $resource_id, $termin_id)) {
                StudipLog::log('OC_SCHEDULE_EVENT', $termin_id, $course_id);

                return true;
            } else {
                // TODO FEEDBACK
            }
        }
    }

    static function updateschedule($resource_id, $termin_id, $course_id)
    {

        $scheduled = OCModel::checkScheduledRecording($course_id, $resource_id, $termin_id);
        if ($scheduled) {
            $scheduler_client = SchedulerClient::getInstance(OCConfig::getConfigIdForCourse($course_id));
            $scheduler_client->updateEventForSeminar($course_id, $resource_id, $termin_id, $scheduled['event_id']);
            StudipLog::log('OC_REFRESH_SCHEDULED_EVENT', $termin_id, $course_id);
        } else {
            self::schedule($resource_id, $termin_id, $course_id);
        }
    }

    static function unschedule($resource_id, $termin_id, $course_id)
    {
        $scheduled = OCModel::checkScheduledRecording($course_id, $resource_id, $termin_id);
        if ($scheduled) {
            $scheduler_client = SchedulerClient::getInstance(OCConfig::getConfigIdForCourse($course_id));

            if ($scheduler_client->deleteEventForSeminar($course_id, $resource_id, $termin_id)) {
                StudipLog::log('OC_CANCEL_SCHEDULED_EVENT', $termin_id, $course_id);

                return true;
            } else {
                // TODO FEEDBACK
            }
        }
    }

    function remove_failed_action($workflow_id)
    {
        $workflow_client = WorkflowClient::getInstance();

        if ($workflow_client->removeInstanceComplete($workflow_id)) {
            if (OCModel::removeWorkflowIDforCourse($workflow_id, $this->course_id)) {
                $this->flash['messages'] = ['success' => $this->_("Die hochgeladenen Daten wurden gelöscht.")];
            } else {
                $this->flash['messages'] = ['error' => $this->_("Die Referenz in der Datenbank konnte nicht gelöscht werden.")];
            }
        } else {
            $this->flash['messages'] = ['error' => $this->_("Die hochgeladenen Daten konnten nicht gelöscht werden.")];
        }

        $this->redirect('course/index/');
    }

    function get_player_action($episode_id = "")
    {

        $course_id = Context::getId();

        $occourse = new OCCourseModel($course_id);
        $episodes = $occourse->getEpisodes();
        $episode = [];
        $current_preview = '';
        foreach ($episodes as $e) {
            if ($e['id'] == $episode_id) {
                $e['author'] = $e['author'] != '' ? $e['author'] : 'Keine Angaben vorhanden';
                $e['description'] = $e['description'] != '' ? $e['description'] : 'Keine Beschreibung vorhanden';
                $e['start'] = date("d.m.Y H:i", strtotime($e['start']));

                $cand_episode = $e;
            }
        }

        if (Request::isXhr()) {

            $this->set_status('200');
            $active_id = $episode_id;
            $this->search_client = SearchClient::getInstance();

            if ($this->paella) {
                $video_url = $this->search_client->getBaseURL() . "/paella/ui/embed.html?id=" . $active_id;
            } else {
                $video_url = $this->search_client->getBaseURL() . "/engage/theodul/ui/core.html?id=" . $active_id;
            }

            $perm = $GLOBALS['perm']->have_studip_perm('dozent', $course_id);

            $plugin = PluginEngine::getPlugin('OpenCast');
            $video = ['url' => $video_url, 'image' => $current_preview, 'circle' => $plugin->getPluginURL() . '/images/play.svg'];

            $episode = [
                'active_id'         => $active_id,
                'course_id'         => $course_id,
                'paella'            => $paella,
                'video'             => $video,
                'perm'              => $perm,
                'episode_data'      => $episode
            ];

            $this->render_json($episode);
        } else {
            $this->redirect(PluginEngine::getLink('opencast/course/index/' . $episode_id));
        }
    }

    function refresh_episodes_action($ticket)
    {

        if (check_ticket($ticket) && $GLOBALS['perm']->have_studip_perm('dozent', $this->course_id)) {
            $occourse2 = new OCCourseModel($this->course_id);
            $occourse2->getEpisodes(true);

            $this->flash['messages'] = ['success' => $this->_("Die Episodenliste wurde aktualisiert.")];
        }

        $this->redirect('course/index/false');
    }

    function toggle_tab_visibility_action($ticket)
    {
        if (check_ticket($ticket) && $GLOBALS['perm']->have_studip_perm('dozent', $this->course_id)) {
            $occourse = new OCCourseModel($this->course_id);
            $occourse->toggleSeriesVisibility();
            $visibility = $occourse->getSeriesVisibility();
            $vis = ['visible' => 'sichtbar', 'invisible' => 'ausgeblendet'];
            $this->flash['messages'] = ['success' => sprintf($this->_("Der Reiter in der Kursnavigation ist jetzt für alle Kursteilnehmer %s."), $vis[$visibility])];
            StudipLog::log('OC_CHANGE_TAB_VISIBILITY', $this->course_id, null, sprintf($this->_("Reiter ist %s."), $vis[$visibility]));
        }
        $this->redirect('course/index/false');
    }

    function toggle_schedule_action($ticket)
    {
        if (check_ticket($ticket) && $GLOBALS['perm']->have_studip_perm('dozent', $this->course_id)) {
            $occourse = new OCCourseModel($this->course_id);
            $occourse->toggleSeriesSchedule();
        }
        $this->redirect('course/index/false');
    }

    function workflow_action()
    {
        if (Request::isXhr()) {
            $this->set_layout(null);
        }

        PageLayout::setTitle($this->_('Workflow konfigurieren'));

        $this->workflow_client = WorkflowClient::getInstance();

        $this->workflows = array_filter(
            $this->workflow_client->getTaggedWorkflowDefinitions(),
            function ($element) {
                return (in_array('schedule', $element['tags']) !== false
                    || in_array('schedule-ng', $element['tags']) !== false)
                    ? $element
                    : false;
            }
        );

        $occourse = new OCCourseModel($this->course_id);
        $this->uploadwf = $occourse->getWorkflow('upload');
    }

    function setworkflow_action()
    {
        if (check_ticket(Request::get('ticket'))
            && $GLOBALS['perm']->have_studip_perm('dozent', $this->course_id)
        ) {

            $occcourse = new OCCourseModel($this->course_id);

            if ($course_workflow = Request::get('oc_course_workflow')) {
                $occcourse->setWorkflow($course_workflow, 'schedule');
            }

            if ($course_uploadworkflow = Request::get('oc_course_uploadworkflow')) {
                $occcourse->setWorkflow($course_uploadworkflow, 'upload');
            }

        }

        $this->redirect('course/index/false');
    }

    function setworkflowforscheduledepisode_action($termin_id, $workflow_id, $resource_id)
    {

        if (Request::isXhr() && $GLOBALS['perm']->have_studip_perm('dozent', $this->course_id)) {
            $occcourse = new OCCourseModel($this->course_id);
            $success = $occcourse->setWorkflowForDate($termin_id, $workflow_id);
            self::updateschedule($resource_id, $termin_id, $this->course_id);
            $this->render_json(json_encode($success));

        } else {
            $this->render_nothing();
        }

    }

    public static function nice_size_text($size, $precision = 1, $conversion_factor = 1000, $display_threshold = 0.5)
    {
        $possible_sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB'];
        for ($depth = 0; $depth < count($possible_sizes); $depth++) {
            if (($size / $conversion_factor) > $display_threshold) {
                $size /= $conversion_factor;
            } else {
                return round($size, $precision) . ' ' . $possible_sizes[$depth];
            }
        }

        return $size;
    }

}
