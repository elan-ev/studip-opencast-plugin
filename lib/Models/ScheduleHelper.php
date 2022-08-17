<?php

namespace Opencast\Models;

use Seminar;
use \DBManager;
use \PDO;

use Opencast\Models\I18N as _;

use Opencast\Models\Resources;
use Opencast\Models\ScheduledRecordings;
use Opencast\Models\SeminarSeries;
use Opencast\Models\Config;
use Opencast\Models\REST\CaptureAgentAdminClient;
use Opencast\Models\REST\WorkflowClient;
use Opencast\Models\REST\IngestClient;
use Opencast\Models\REST\SchedulerClient;
use Opencast\Models\REST\ApiEventsClient;

class ScheduleHelper
{
    /**
     * Gets the list of semesters for the course to be repsresented in the semster filter dropdown
     * NOTE: expired semesters are rejected!
     * 
     * @param string $seminar_id id of course
     * 
     * @return array
     */
    public static function getSemesterList($seminar_id)
    {
        $course = Seminar::getInstance($seminar_id);
        $selectable_semesters = new \SimpleCollection(\Semester::getAll());
        $start = $course->start_time;
        $end = $course->duration_time == -1 ? PHP_INT_MAX : $course->end_time;
        $selectable_semesters = $selectable_semesters->findBy('beginn', [$start, $end], '>=<=')->toArray();
        if (count($selectable_semesters) > 1 || (count($selectable_semesters) == 1 && $course->hasDatesOutOfDuration())) {
            $selectable_semesters[] = ['name' => 'Alle Semester', 'id' => 'all'];
        }
        $selectable_semesters = array_values(array_reverse($selectable_semesters));

        return $selectable_semesters;
    }

    /**
     * Prepares the config scheduling table
     *
     * @param array $config_list the list of configure oc servers
     * @param array $resources the list studip resources 
     *
     * @return array $scheduling the data to be displayed in scheduling config
     */
    public static function prepareSchedulingConfig($config_list, $resources)
    {
        $scheduling = [];
        $resourse_list = [];
        $capture_agents = self::getCaptureAgentsConfig($config_list);
        $workflow_definitions = self::getWorkflowDefinitionsConfig($config_list);

        foreach ($resources as $resource) {
            $assigned_ca = null;
            $assigned_wd = null;
            if ($assigned_resource = Resources::findByResource_id($resource['id'])) {
                $ca_valid = false;
                $wd_valid = false;
                foreach ($capture_agents as $key => $agent) {
                    if ($agent->name == $assigned_resource['capture_agent']
                        && $agent->config_id == $assigned_resource['config_id']) {
                        $capture_agents[$key]->in_use = true;
                        $assigned_ca = $capture_agents[$key];
                        $ca_valid = true;
                    }
                }
                if (!empty($assigned_ca)) {
                    foreach ($workflow_definitions as $key => $definition) {
                        if ($definition->id == $assigned_resource['workflow_id']
                            && $definition->config_id == $assigned_ca->config_id) {
                            $assigned_wd = $workflow_definitions[$key];
                            $wd_valid = true;
                        }
                    }
                }

                // Record validation happens here.
                // If capture agent doen not exist, we remove the record.
                if (!$ca_valid) {
                    Resources::removeResource($resource['id']);
                } else if ($ca_valid && !$wd_valid) { // If workflow doen not exist, we update the record.
                    Resources::setResource($resource['id'], $resource['config_id'], $resource['capture_agent'], null);
                }
            }
            $resourse_list[] = [
                'id' => $resource['id'],
                'name' => $resource['name'],
                'capture_agent' => !empty($assigned_ca) ? $assigned_ca->name : '',
                'config_id' => !empty($assigned_ca) ? $assigned_ca->config_id : '',
                'workflow_id' => !empty($assigned_wd) ? $assigned_wd->id : '',
            ];
        }

        $scheduling['resources'] = $resourse_list;
        $scheduling['capture_agents'] = $capture_agents;
        $scheduling['workflow_definitions'] = $workflow_definitions;
        
        return $scheduling;
    }

    /**
     * Gets the icon object based on capture agent status
     *
     * @param string $state the state of capture agent
     *
     * @return object|string icon object or empty string
     */
    private static function getCaptuteAgentStatusIcon($state)
    {
        if (!empty($state)) {
            switch ($state) {
                case 'idle':
                    return \Icon::create('pause', \Icon::ROLE_CLICKABLE, ['title' => 'Idle'])->asImg();
                    break;
                case 'unknown':
                    return \Icon::create('question', \Icon::ROLE_CLICKABLE, ['title' => 'Status unbekannt'])->asImg();
                    break;
                default:
                    return \Icon::create('video', \Icon::ROLE_CLICKABLE, ['title' => 'Beschäftigt'])->asImg();
                    break;
            }
        }
        return '';
    }

    /**
     * Gets the list of capture agent configs to be consumed when displaying info in scheduling config 
     *
     * @param array $config_list the list of configure oc servers
     *
     * @return array $capture_agents list of modified capture agents
     */
    private static function getCaptureAgentsConfig($config_list)
    {
        $capture_agents = [];
        foreach ($config_list as $config) {
            try {
                $caa_client = CaptureAgentAdminClient::getInstance($config['id']);
                foreach ($caa_client->getCaptureAgents() as $capture_agent) {
                    $capture_agent_obj = new \stdClass();
                    $capture_agent_obj->config_id = $config['id'];
                    $capture_agent_obj->name = $capture_agent->name;
                    $capture_agent_obj->state = $capture_agent->state;
                    $capture_agent_obj->icon = self::getCaptuteAgentStatusIcon($capture_agent->state);
                    $capture_agent_obj->in_use = false;
                    $capture_agents[] = $capture_agent_obj;
                }
            } catch (\Throwable $th) {
            }
            
        }
        return $capture_agents;
    }

    /**
     * Gets the list of workflow definitions to be consumed when displaying info in scheduling config
     *
     * @param array $config_list the list of configure oc servers
     *
     * @return array $definitions list of modified workflow definitions
     */
    private static function getWorkflowDefinitionsConfig($config_list)
    {
        $definitions = [];
        foreach ($config_list as $config) {
            try {
                $workflow_client = WorkflowClient::getInstance($config['id']);
                if ($oc_definitions = $workflow_client->getDefinitions()) {
                    foreach ($oc_definitions as $definition) {
                        if (is_object($definition->tags)) {
                            if (is_array($definition->tags->tag) &&
                                (in_array('schedule', $definition->tags->tag) ||
                                in_array('schedule-ng', $definition->tags->tag))) 
                            {
                                $resources_workflow_def = new \stdClass();
                                $resources_workflow_def->config_id = $config['id'];
                                $resources_workflow_def->id = $definition->id;
                                $resources_workflow_def->title = $definition->title;
                                $definitions[] = $resources_workflow_def;
                            } else if ($definition->tags->tag == 'schedule' || $definition->tags->tag == 'schedule-ng') {
                                $resources_workflow_def = new \stdClass();
                                $resources_workflow_def->config_id = $config['id'];
                                $resources_workflow_def->id = $definition->id;
                                $resources_workflow_def->title = $definition->title;
                                $definitions[] = $resources_workflow_def;
                            }
                        }
                    }
                }
            } catch (\Throwable $th) {
            }
        }
        return $definitions;
    }

    /**
     * Checks if a capture agent exists for a specific oc server
     * 
     * @param int $config_id the id of oc server config
     * @param string $capture_agent the name of capture agent
     * 
     * @return bool
     */
    private static function checkCaptureAgent($config_id, $capture_agent)
    {
        $caa_client = CaptureAgentAdminClient::getInstance($config_id);
        if ($caa_client) {
            $existing_ca_list = $caa_client->getCaptureAgents();
            $existing_ca = array_filter($existing_ca_list, function ($ca) use ($capture_agent) {
                return $ca->name == $capture_agent;
            });
            return count($existing_ca) > 0;
        }
        return false;
    }

    /**
     * Creates an xml representation for a new Scheduled Event
     * 
     * @param string course_id course id
     * @param string resource_id resource id
     * @param string $termin_id termin id
     * @param string $event_id oc event id
     * @param int $buffer the buffer range
     * 
     * @return string xml - the xml representation of the string
     */
    public static function createScheduleEventXML($course_id, $resource_id, $termin_id, $event_id, $buffer)
    {
        date_default_timezone_set("Europe/Berlin");
        
        $course = Seminar::getInstance($course_id);
        $date = new \SingleDate($termin_id);
        
        // if event_id is null, there is not yet an event which could have other start or end-times
        if ($event_id) {
            $event = ScheduledRecordings::find($event_id);
        }
        
        $issues = $date->getIssueIDs();
        
        $issue_titles = array();
        if(is_array($issues)) {
            foreach($issues as $is) {
                $issue = new \Issue(array('issue_id' => $is));
                if(sizeof($issues) > 1) {
                    $issue_titles[] =  my_substr($issue->getTitle(), 0 ,80 );
                } else $issue_titles =  my_substr($issue->getTitle(), 0 ,80 );
            }
            if(is_array($issue_titles)){
                $issue_titles = _("Themen: ") . my_substr(implode(', ', $issue_titles), 0 ,80 );
            }
        }
        
        
        $series = SeminarSeries::getSeries($course_id);
        $serie = $series[0];
        
        $ca = Resources::findByResource_id($resource_id);
        
        $creator = 'unknown';

        if ($GLOBALS['perm']->have_perm('admin')) {
            $instructors = $course->getMembers('dozent');
            $instructor = array_shift($instructors);
            $creator    = $instructor['fullname'];
        } else {
            $creator    = get_fullname();
        }
        
        $inst_data = \Institute::find($course->institut_id);
        
        $start_time = $event_id ? $event->start : $date->getStartTime();
        
        if ($buffer) {
            $end_time = strtotime("-$buffer seconds ", intval($event_id ? $event->end : $date->getEndTime()));
        } else {
            $end_time = $event_id ? $event->end : $date->getEndTime();
        }
        
        $contributor = $inst_data['name'];
        $description = $issue->description;
        $device = $ca['capture_agent'];
        
        $language = "de";
        $seriesId = $serie['series_id'];
        
        if (!$issue->title) {
            $name = $course->getName();
            $title = $name . ' ' . sprintf(_('(%s)'), $date->getDatesExport());
        } else $title = $issue_titles;
        
        
        // Additional Metadata
        $abstract = $course->description;
        
        $dublincore = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
        <dublincore xmlns="http://www.opencastproject.org/xsd/1.0/dublincore/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
        <dcterms:creator><![CDATA[' . $creator . ']]></dcterms:creator>
        <dcterms:contributor><![CDATA[' . $contributor . ']]></dcterms:contributor>
        <dcterms:created xsi:type="dcterms:W3CDTF">' . self::getDCTime($start_time) . '</dcterms:created>
        <dcterms:temporal xsi:type="dcterms:Period">start='. self::getDCTime($start_time) .'; end='. self::getDCTime($end_time) .'; scheme=W3C-DTF;</dcterms:temporal>
        <dcterms:description><![CDATA[' . $description . ']]></dcterms:description>
        <dcterms:subject><![CDATA[' . $abstract . ']]></dcterms:subject>
        <dcterms:language><![CDATA[' . $language . ']]></dcterms:language>
        <dcterms:spatial>' . $device . '</dcterms:spatial>
        <dcterms:title><![CDATA[' . $title . ']]></dcterms:title>
        <dcterms:isPartOf>'. $seriesId . '</dcterms:isPartOf>
        </dublincore>';
        
        return $dublincore;

    }

    /**
     * Gets datetime in Dublincore format.
     * 
     * @param int $timestamp the timestamp
     * 
     * @return string|bool
     */
    private static function getDCTime($timestamp)
    {
        return gmdate("Y-m-d", $timestamp).'T'.gmdate('H:i:s', $timestamp).'Z';
    }

    /**
     * Schedules an event for a give course
     * NOTE: it performs the creating of scheduled record on both sides (SOP & OC)
     *
     * @param string $course_id - course identifier
     * @param string $termin_id - termin identifier
     * @param bool $livestream - livestream flag
     *
     * @return bool success or not
     */
    public static function scheduleEventForSeminar($course_id, $termin_id, $livestream = false)
    {
        $date = new \SingleDate($termin_id);
        $resource_id = $date->getResourceID();
        if (!$resource_id) {
            return false;
        }
        $oc_resource  = Resources::findByResource_id($resource_id);
        if (!$oc_resource
            || !self::checkCaptureAgent($oc_resource['config_id'], $oc_resource['capture_agent'])
            || !self::validateCourseAndResource($course_id, $oc_resource['config_id'])) {
            return false;
        }

        $ingest_client = IngestClient::getInstance($oc_resource['config_id']);
        $media_package = $ingest_client->createMediaPackage();
        $metadata      = self::createEventMetadata($course_id, $resource_id, $oc_resource['config_id'], $termin_id, null);
        $media_package = $ingest_client->addDCCatalog($media_package, $metadata['dublincore']);

        $result = $ingest_client->schedule($media_package, $metadata['workflow'], $metadata['device_capabilities'], $livestream);

        if ($result) {

            $xml = simplexml_load_string($media_package);
            $event_id = (string)$xml['id'];
            $scheduled = self::scheduleRecording($course_id, $resource_id, $termin_id, $event_id);
            if ($scheduled) {
                return true;
            }
            // If it hits here, it means opencast has the record but local database doesn't,
            // Therefore, we remove it from opencast as well.
            $scheduler_client = SchedulerClient::getInstance($oc_resource['config_id']);
            $scheduler_client->deleteEvent($event_id);
        }
        return false;
    }

    /**
     * Checks if a course server config matches the resource server config 
     * 
     * @param string $course_id course id
     * @param int $resource_config_id resource config id
     * 
     * @return bool
     */
    public static function validateCourseAndResource($course_id, $resource_config_id)
    {
        $course_config_id = Config::getConfigIdForCourse($course_id);
        return intval($course_config_id) === intval($resource_config_id);
    }

    /**
     * Schedules a recording for a given date and resource within a course
     *
     * @param string $course_id
     * @param string $resource_id
     * @param string $date_id - Stud.IP Identifier for the event
     * @param string $event_id  - Opencast Identifier for the event
     * 
     * @return boolean success
     */
    private static function scheduleRecording($course_id, $resource_id, $date_id, $event_id)
    {
        $series = SeminarSeries::getSeries($course_id);
        $serie = $series[0];

        $ca = Resources::findByResource_id($resource_id);
        $workflow_id = $ca['workflow_id'] ? $ca['workflow_id'] : 'full';

        $date = \CourseDate::find($date_id);

        $success = ScheduledRecordings::setScheduleRecording(
            $course_id,
            $serie['series_id'],
            $date_id,
            $resource_id,
            $date->date,
            $date->end_time,
            $ca['capture_agent'],
            $event_id,
            'scheduled',
            $workflow_id
        );

        return $success;
    }

    /**
     * Creates event recording metadata
     * 
     * @param string $course_id course id
     * @param string $resource_id resource id
     * @param string $config_id server config id
     * @param string $termin_id termin id
     * @param string $event_id event id
     * 
     * @return array event recording metadata
     */
    private static function createEventMetadata($course_id, $resource_id, $config_id, $termin_id, $event_id)
    {
        $config = Config::find($config_id);
        
        $buffer = isset($config['settings']['time_buffer_overlap']) ? $config['settings']['time_buffer_overlap'] : 0;
        $dublincore = self::createScheduleEventXML(
            $course_id, $resource_id, $termin_id, $event_id, $buffer
        );

        $date = new \SingleDate($termin_id);

        $issue_titles = [];
        $issues       = $date->getIssueIDs();

        if (is_array($issues)) {
            foreach ($issues as $is) {
                $issue = new \Issue(['issue_id' => $is]);

                if (sizeof($issues) > 1) {
                    $issue_titles[] = my_substr(kill_format($issue->getTitle()), 0, 80);
                } else {
                    $issue_titles = my_substr(kill_format($issue->getTitle()), 0, 80);
                }
            }

            if (is_array($issue_titles)) {
                $issue_titles = _("Themen: ") . my_substr(implode(', ', $issue_titles), 0, 80);
            }
        }

        if (!$issue->title) {
            $course = Seminar::getInstance($course_id);
            $name   = $course->getName();
            $title  = $name . ' ' . sprintf(_('(%s)'), $date->getDatesExport());
        } else {
            $title = $issue_titles;
        }

		if (\StudipVersion::newerThan('4.4')) {
        	$room = new \Resource($resource_id);
        } else {
        	$room = \ResourceObject::Factory($resource_id);
        }
        $ca  = Resources::findByResource_id($resource_id);

        $device   = $ca['capture_agent'];
        $workflow = $ca['workflow_id'];

        $ca_client    = CaptureAgentAdminClient::getInstance($config_id);
        $device_names = '';
        $capabilities = $ca_client->getCaptureAgentCapabilities($ca['capture_agent']);

        if (isset($capabilities)) {
            foreach ($capabilities as $capability) {
                if ($capability->key == 'capture.device.names') {
                    $device_names = $capability->value;
                }
            }
        }

        $agentparameters = 'event.title=' . $title . "\n"
            . 'event.location=' . $room->name . "\n"
            . 'capture.device.id=' . $device . "\n"
            . 'capture.device.names=' . $device_names . "\n"
            . 'org.opencastproject.workflow.definition=' . $workflow . "\n";

        return [
            'device_capabilities' => $device_names,
            'dublincore'          => $dublincore,
            'agentparameters'     => $agentparameters,
            'workflow'            => $workflow,
            'agent'               => $device
        ];
    }

    /**
     * Deletes a scheduled event
     * NOTE: it performs the deletion of scheduled record on both sides (SOP & OC)
     *
     * @param string $course_id - course identifier
     * @param string $termin_id - termin identifier
     *
     * @return bool success or not
     */
    public static function deleteEventForSeminar($course_id, $termin_id)
    {
        $date = new \SingleDate($termin_id);
        $resource_id = $date->getResourceID();
        if (!$resource_id) {
            return false;
        }

        $event_data = ScheduledRecordings::checkScheduled($course_id, $resource_id, $termin_id);
        
        if (!$event_data) {
            return false;
        }
        $event_id = $event_data['event_id'];

        $resource_obj = Resources::findByResource_id($resource_id);
        if (!$resource_obj) {
            return false;
        }

        $scheduler_client = SchedulerClient::getInstance($resource_obj['config_id']);

        $result = $scheduler_client->deleteEvent($event_id);

        if ($result) {
            ScheduledRecordings::unscheduleRecording($event_id, $resource_id, $termin_id);
            \StudipLog::log('OC_CANCEL_SCHEDULED_EVENT', $termin_id, $course_id);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Updates an scheduled event
     * NOTE: it performs updating of scheduled record on both sides (SOP & OC)
     * this function will be used upon time range cahnges with Slider
     *
     * @param string $course_id - course identifier
     * @param string $termin_id - termin identifier
     * @param int $start - the start timestamp
     * @param int $end - the end timestamp
     *
     * @return bool success or not
     */
    public static function updateEventForSeminar($course_id, $termin_id, $start = null, $end = null)
    {
        $date = new \SingleDate($termin_id);
        $resource_id = $date->getResourceID();
        if (!$resource_id) {
            return false;
        }

        $resource_obj = Resources::findByResource_id($resource_id);
        if (!$resource_obj) {
            return false;
        }

        $config = Config::find($resource_obj['config_id']);
        if (!$config || !self::validateCourseAndResource($course_id, $resource_obj['config_id'])) {
            return false;
        }

        $buffer = isset($config['settings']['time_buffer_overlap']) ? $config['settings']['time_buffer_overlap'] : 0;

        $event_data = ScheduledRecordings::checkScheduled($course_id, $resource_id, $termin_id);
        
        if (!$event_data) {
            return false;
        }
        $event_id = $event_data['event_id'];


        // currently, we only update the start and the end time
        $event = ScheduledRecordings::find($event_id);
        $date  = \CourseDate::find($termin_id);

        $new_start = 0;
        if (!is_null($start) && \Config::get()->OPENCAST_ALLOW_ALTERNATE_SCHEDULE) {
            $new_start = mktime(
                floor($start / 60),
                $start - floor($start / 60) * 60,
                0,
                date('n', $date->date),
                date('j', $date->date),
                date('Y', $date->date)
            );
        } else if ($date->date > $event->start) {
            $new_start = $date->date;
        }
        if (!empty($new_start)) {
            $event->start = $new_start;
            $event->store();
        }

        $new_end = 0;
        if (!is_null($end) && \Config::get()->OPENCAST_ALLOW_ALTERNATE_SCHEDULE) {
            $new_end = mktime(
                floor($end / 60),
                $end - floor($end / 60) * 60,
                0,
                date('n', $date->date),
                date('j', $date->date),
                date('Y', $date->date)
            );
        } else if ($date->end_time < $event->end) {
            $new_end = $date->end_time;
        }
        if (!empty($new_end)) {
            $event->end = $new_end;
            $event->store();
        }

        $metadata = self::createEventMetadata($course_id, $resource_id, $resource_obj['config_id'], $termin_id, $event_id);

        $start = $event->start * 1000;
        $end = ($event->end - $buffer ?: 0) * 1000;
        $agentparameters = $metadata['agentparameters'];
        $agent = $metadata['agent'];

        $scheduler_client = SchedulerClient::getInstance($resource_obj['config_id']);

        $result = $scheduler_client->updateEvent(
            $event_id,
            $start,
            $end,
            $agent,
            '',
            '',
            '',
            $agentparameters
        );

        if ($result) {
            \StudipLog::log('OC_REFRESH_SCHEDULED_EVENT', $termin_id, $course_id);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Gets the livestream parameter of the course's server config (if any)
     * 
     * @param string $course_id course id
     * 
     * @return bool
     */
    public static function checkCourseConfigLivestream($course_id)
    {
        $livestream = false;
        $config = Config::getConfigForCourse($course_id);
        if ($config && isset($config['settings']['livestream'])) {
            $livestream = $config['settings']['livestream'];
        }
        return $livestream;
    }

    /**
     * Gets the list of scheduling dates for a course to be displayed in the scheduling list in a course
     * 
     * @param string $course_id course id
     * @param string $semester_filter semester id
     * 
     * @return array the scheudling list to be displayed in a course
     */
    public static function getScheduleList($course_id, $semester_filter)
    {
        $allow_schedule_alternate = \Config::get()->OPENCAST_ALLOW_ALTERNATE_SCHEDULE;
        $allow_livestream = self::checkCourseConfigLivestream($course_id);

        $dates = self::getDatesForSemester($course_id, $semester_filter);
        $events = self::getCourseEvents($course_id);
        $schedule_list = [];
        foreach ($dates as $d) {
            $date_obj = [];

            $date = new \SingleDate($d['termin_id']);
            $date_obj['termin_id'] = $date->termin_id;
            $date_obj['termin_title'] = $date->getDatesHTML();
            $resource_id = $date->getResourceID();

            $resource_obj = !empty($resource_id) ? Resources::findByResource_id($resource_id) : null;

            $date_obj['resource_id'] = $resource_id;
            $scheduled = ScheduledRecordings::checkScheduled($course_id, $resource_id, $date->termin_id);
            $allow_bulk = false;
            if (!empty($resource_obj) && (date($d['date']) > time())) {
                $allow_bulk = true;
            }
            $date_obj['allow_bulk'] = $allow_bulk;
            if ($allow_schedule_alternate) {
                $recording_period = 'keine Aufzeichnung geplant';
                if ($scheduled && date($d['date']) > time()) {
                    $recording_period = [
                        'range_start' => (date('G', $date->date) * 60 + date('i', $date->date)),
                        'range_end' => (date('G', $date->end_time) * 60 + date('i', $date->end_time)),
                        'start' => (date('G', $scheduled['start']) * 60 + date('i', $scheduled['start'])),
                        'end' => (date('G', $scheduled['end']) * 60 + date('i', $scheduled['end'])),
                        'event_id' => $scheduled['event_id']
                    ];
                }
                $date_obj['recording_period'] = $recording_period;
            }
            $title = 'Kein Titel eingetragen';
            if ($issues = $date->getIssueIDs()) {
                if (is_array($issues)) {
                    if (sizeof($issues) > 1) {
                        $titles = [];
                        foreach ($issues as $is) {
                            $issue = new \Issue(['issue_id' => $is]);
                            $titles[] = my_substr($issue->getTitle(), 0, 80);
                        }
                        $title = count($titles) ? 'Themen: ' . htmlReady(my_substr(implode(', ', $titles), 0, 80)) : '';
                    } else {
                        foreach ($issues as $is) {
                            $issue = new \Issue(['issue_id' => $is]);
                            $title = htmlReady(my_substr($issue->getTitle(), 0, 80));
                        }
                    }
                }
            }
            $date_obj['title'] = $title;
            $status = [
                'shape' => 'exclaim-circle',
                'role' => 'attention',
                'title' => _('Es wurde bislang kein Raum mit Aufzeichnungstechnik gebucht.')
            ];
            if (!empty($resource_obj)) {
                if ($scheduled) {
                    $status = [
                        'shape' => 'video',
                        'role' => 'info',
                        'title' => _('Aufzeichnung ist bereits geplant.')
                    ];
                    if ($scheduled && in_array('engage-live', $events[$scheduled['event_id']]->publication_status)) {
                        $status['info'] = 'LIVE';
                    }
                } else {
                    if (date($d['date']) > time()) {
                        $status = [
                            'shape' => 'date',
                            'role' => 'info',
                            'title' => _('Aufzeichnung ist noch nicht geplant.')
                        ];
                    } else {
                        $status = [
                            'shape' => 'exclaim-circle',
                            'role' => 'info',
                            'title' => _('Dieses Datum liegt in der Vergangenheit. Sie können keine Aufzeichnung planen.')
                        ];
                    }
                }
            }
            $date_obj['status'] = $status;
            
            $actions = [];
            if (!empty($resource_obj)) {
                if ($scheduled && (int)date($d['date']) > time()) {
                    $actions['updateSchedule'] = [
                        'shape' => 'refresh',
                        'role' => 'clickable',
                        'title' => _('Aufzeichnung ist bereits geplant. Sie können die Aufzeichnung stornieren oder entsprechende Metadaten aktualisieren.')
                    ];
                    $actions['unschedule'] = [
                        'shape' => 'trash',
                        'role' => 'clickable',
                        'title' => _('Aufzeichnung ist bereits geplant. Klicken Sie hier um die Planung zu aufzuheben.')
                    ];
                } else {
                    if (date($d['date']) > time()) {
                        $actions['schedule'] = [
                            'shape' => 'video',
                            'role' => 'clickable',
                            'title' => _('Aufzeichnung planen')
                        ];
                        if ($allow_livestream) {
                            $actions['scheduleLive'] = [
                                'shape' => 'video',
                                'role' => 'clickable',
                                'title' => _('Livestream+Aufzeichnung planen'),
                                'info' => 'LIVE'
                            ];
                        }
                    } else {
                        $actions['expire'] = [
                            'shape' => 'video+decline',
                            'role' => 'inactive',
                            'title' => _('Dieses Datum liegt in der Vergangenheit. Sie können keine Aufzeichnung planen.')
                        ];
                    }
                }
            }
            $date_obj['actions'] = $actions;

            $schedule_list[] = $date_obj;
        }

        return $schedule_list;
    }

    /**
     * Gets the scheduled course dates based on a semester
     * 
     * @param string $seminar_id course id
     * @param string $semester_id semester id
     * 
     * @return array course dates list
     */
    private static function getDatesForSemester($seminar_id, $semester_id = null)
    {
        $today = strtotime('today midnight');
        if ($semester_id == 'all' || is_null($semester_id)) {
            // get all dates
            $stmt = DBManager::get()->prepare("SELECT * FROM `termine`
                WHERE `range_id` = ?
                AND `date` >= ?
                ORDER BY `date` ASC");
            $stmt->execute([$seminar_id, $today]);
        } else {
            // get dates for selected semester only
            $semester = \Semester::find($semester_id);

            $stmt = DBManager::get()->prepare("SELECT * FROM `termine`
                WHERE `range_id` = ?
                    AND `date` >= ?
                    AND `date` < ?
                ORDER BY `date` ASC");
            $stmt->execute([$seminar_id, max($semester->beginn, $today) , $semester->ende]);
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Gets a list of all oc events for a course (based on its related series id)
     * 
     * @param string $course_id course id
     * 
     * @return array $course_events oc events of the course
     */
    private static function getCourseEvents($course_id)
    {
        $course_events = [];
        $config = Config::getConfigForCourse($course_id);
        if ($config) {
            $series = SeminarSeries::getSeries($course_id);
            if (isset($series[0]['series_id'])) {
                $events_client = ApiEventsClient::getInstance($config['id']);
                $events = $events_client->getAll([
                    'filter' => ['is_part_of' => $series[0]['series_id']],
                    'withpublications' => true
                ]);
                if (!empty($events)) {
                    foreach ($events as $event) {
                        $course_events[$event->identifier] = $event;
                    }
                }
            }
        }
        return $course_events;
    }
}
