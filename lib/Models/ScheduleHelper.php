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
use Opencast\Models\PlaylistSeminars;
use Opencast\Models\Videos;
use Opencast\Models\Helpers;
use Opencast\Models\PlaylistVideos;
use Opencast\Models\PlaylistSeminarVideos;

class ScheduleHelper
{
    const LIVESTREAM_STATUS_SCHEDULED = 'scheduled';
    const LIVESTREAM_STATUS_LIVE = 'live';
    const LIVESTREAM_STATUS_FINISHED = 'finished';
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

        $selectable_semesters[] = ['name' => _('Alle Semester'), 'id' => 'all'];

        $selectable_semesters = array_values(array_reverse($selectable_semesters));

        array_walk($selectable_semesters, function(&$semester) {
            array_walk($semester, function(&$value, $key) {
                if ($key == 'name' || $key == 'description') {
                    $value = (string)$value;
                }
            });
        });

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
            $assigned_livestream_wd = null;
            if ($assigned_resource = Resources::findByResource_id($resource['id'])) {
                $ca_valid = false;
                $wd_valid = false;
                $wd_livestream_valid = false;
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
                        if ($definition->id == $assigned_resource['livestream_workflow_id']
                            && $definition->config_id == $assigned_ca->config_id) {
                            $assigned_livestream_wd = $workflow_definitions[$key];
                            $wd_livestream_valid = true;
                        }
                    }
                }
            }
            $resourse_list[] = [
                'id' => $resource['id'],
                'name' => $resource['name'],
                'capture_agent' => !empty($assigned_ca) ? $assigned_ca->name : '',
                'config_id' => !empty($assigned_ca) ? $assigned_ca->config_id : '',
                'workflow_id' => !empty($assigned_wd) ? $assigned_wd->id : '',
                'livestream_workflow_id' => !empty($assigned_livestream_wd) ? $assigned_livestream_wd->id : '',
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
                $issue_titles = _("Themen: ") . my_substr(implode(', ', (array)$issue_titles), 0 ,80 );
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
        $description = $issue->description ?? null;
        $device = $ca['capture_agent'];

        $language = "de";
        $seriesId = $serie['series_id'];

        if (empty($issue->title)) {
            $name = $course->getName();
            $title = $name . ' ' . sprintf(_('(%s)'), $date->getDatesExport());
        } else {
            $title = $issue_titles;
        }

        // Additional Metadata
        $abstract = $course->description;

        $dublincore = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
                            <dublincore xmlns="http://www.opencastproject.org/xsd/1.0/dublincore/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                                <dcterms:creator><![CDATA[' . $creator . ']]></dcterms:creator>
                                <dcterms:contributor><![CDATA[' . $contributor . ']]></dcterms:contributor>
                                <dcterms:created xsi:type="dcterms:W3CDTF">' . self::getDCTime($start_time) . '</dcterms:created>
                                <dcterms:temporal xsi:type="dcterms:Period">start=' . self::getDCTime($start_time) . '; end=' . self::getDCTime($end_time) . '; scheme=W3C-DTF;</dcterms:temporal>
                                <dcterms:description><![CDATA[' . $description . ']]></dcterms:description>
                                <dcterms:subject><![CDATA[' . $abstract . ']]></dcterms:subject>
                                <dcterms:language><![CDATA[' . $language . ']]></dcterms:language>
                                <dcterms:spatial>' . $device . '</dcterms:spatial>
                                <dcterms:title><![CDATA[' . $title . ']]></dcterms:title>
                                <dcterms:isPartOf>' . $seriesId . '</dcterms:isPartOf>
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
     * @param bool $livestream - indicator to schedule the event with livestream capability
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
            || !self::validateCourseAndResource($course_id, $oc_resource['config_id'])
        ) {
            return false;
        }

        // Livestream workflow checker.
        if (($livestream && empty($oc_resource['livestream_workflow_id']))) {
            // Unlike normal scheduling, we don't allow livestream to be scheduled without a workflow!
            return false;
        }

        $ingest_client = IngestClient::getInstance($oc_resource['config_id']);
        $media_package = $ingest_client->createMediaPackage();
        $metadata      = self::createEventMetadata($course_id, $resource_id, $oc_resource['config_id'], $termin_id, null, $livestream);
        $media_package = $ingest_client->addDCCatalog($media_package, $metadata['dublincore']);

        $result = $ingest_client->schedule($media_package, $metadata['workflow'], $metadata['device_capabilities']);

        if ($result) {
            $xml = simplexml_load_string($media_package);
            $event_id = (string)$xml['id'];
            $scheduled = self::scheduleRecording($course_id, $resource_id, $termin_id, $event_id, $livestream);
            if ($scheduled) {
                // Create the video if it is livestream.
                if ($livestream) {
                    self::createOrUpdateLivestreamVideo($oc_resource['config_id'], $course_id, $event_id);
                }
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
     * @param bool $livestream  - livestream indicator
     *
     * @return boolean success
     */
    private static function scheduleRecording($course_id, $resource_id, $date_id, $event_id, $livestream)
    {
        global $user;

        $series = SeminarSeries::getSeries($course_id);
        $serie = $series[0];

        $ca = Resources::findByResource_id($resource_id);

        $workflow_id = '';
        if ($livestream) {
            $workflow_id = $ca['livestream_workflow_id'];
        } else {
            $workflow_id = $ca['workflow_id'] ?? 'full';
        }

        // Here again the checker for livestream, to prevent scheduling without a workflow.
        if (empty($workflow_id)) {
            return false;
        }

        $date = \CourseDate::find($date_id);

        $success = ScheduledRecordings::setScheduleRecording(
            $course_id,
            $serie['series_id'],
            $user->id,
            $date_id,
            $resource_id,
            $date->date,
            $date->end_time,
            $ca['capture_agent'],
            $event_id,
            'scheduled',
            $workflow_id,
            $livestream
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
     * @param bool $livestream whether the scheduling is intended to be a livestream
     *
     * @return array event recording metadata
     */
    private static function createEventMetadata($course_id, $resource_id, $config_id, $termin_id, $event_id, $livestream = false)
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
                $issue_titles = _("Themen: ") . my_substr(implode(', ', (array)$issue_titles), 0, 80);
            }
        }

        if (empty($issue->title)) {
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
        $workflow = $livestream ? $ca['livestream_workflow_id'] : $ca['workflow_id'];

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
     * @param int $external_config_id - an external server config id to get SchedulerClient instance from
     *
     * @return bool success or not
     */
    public static function deleteEventForSeminar($course_id, $termin_id, $external_config_id = null)
    {
        $date = new \SingleDate($termin_id);
        $resource_id = $date->getResourceID();
        if (!$resource_id) {
            return false;
        }

        $scheduled_recording_obj = ScheduledRecordings::checkScheduled($course_id, $resource_id, $termin_id);

        if (!$scheduled_recording_obj) {
            return false;
        }
        $is_livestream = (bool) $scheduled_recording_obj['is_livestream'];
        $event_id = $scheduled_recording_obj['event_id'];

        $config_id = null;
        if (empty($external_config_id)) {
            $resource_obj = Resources::findByResource_id($resource_id);
            $config_id = $resource_obj ? $resource_obj['config_id'] : null;
        } else {
            $config_id = $external_config_id;
        }

        if (empty($config_id)) {
            return false;
        }

        $scheduler_client = SchedulerClient::getInstance($config_id);

        $result = $scheduler_client->deleteEvent($event_id);

        if ($result) {
            ScheduledRecordings::unscheduleRecording($event_id, $resource_id, $termin_id);
            // Remove the livestream video here!
            if ($is_livestream) {
                self::removeLivestreamVideo($event_id);
            }
            \StudipLog::log('OC_CANCEL_SCHEDULED_EVENT', $termin_id, $course_id);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Updates an scheduled event
     * NOTE: it performs updating of scheduled record on both sides (SOP & OC)
     * this function will be used upon time range change with Slider
     *
     * @param string $course_id - course identifier
     * @param string $termin_id - termin identifier
     * @param int $start - the start timestamp
     * @param int $end - the end timestamp
     * @param bool $update_resource - whether to update the resources info of scheduled recording object
     * @param bool $force_oc_update - a flag to force update recordings on opencast.
     *
     * @return bool success or not
     */
    public static function updateEventForSeminar($course_id, $termin_id, $start = null, $end = null, $update_resource = false,
        $force_oc_update = false)
    {
        $has_changes = false;
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

        $scheduled_recording_obj = ScheduledRecordings::checkScheduled($course_id, $resource_id, $termin_id);

        if (!$scheduled_recording_obj) {
            return false;
        }
        $event_id = $scheduled_recording_obj['event_id'];

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
        } else if ($date->date > $scheduled_recording_obj->start) {
            $new_start = $date->date;
        }
        if (!empty($new_start)) {
            $scheduled_recording_obj->start = $new_start;
            $scheduled_recording_obj->store();
            $has_changes = true;
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
        } else if ($date->end_time < $scheduled_recording_obj->end) {
            $new_end = $date->end_time;
        }
        if (!empty($new_end)) {
            $scheduled_recording_obj->end = $new_end;
            $scheduled_recording_obj->store();
            $has_changes = true;
        }

        $is_livestream = (bool) $scheduled_recording_obj->is_livestream;

        // Update resource
        if ($update_resource) {
            $scheduled_recording_obj->resource_id = $resource_id;
            $scheduled_recording_obj->capture_agent = $resource_obj['capture_agent'];
            $workflow_id = $is_livestream ? $resource_obj['livestream_workflow_id'] : $resource_obj['workflow_id'];
            $scheduled_recording_obj->workflow_id = $workflow_id;
            $scheduled_recording_obj->store();
            $has_changes = true;
        }

        if (!$force_oc_update && !$has_changes) {
            return true;
        }

        $metadata = self::createEventMetadata($course_id, $resource_id, $resource_obj['config_id'], $termin_id, $event_id, $is_livestream);

        $start = $scheduled_recording_obj->start * 1000;
        $end = ($scheduled_recording_obj->end - $buffer ?: 0) * 1000;
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
            // Create the video if it is livestream and it is now finished!
            $now_miliseconds = time() * 1000;
            if ($is_livestream && !empty($end) && $now_miliseconds < $end) {
                self::createOrUpdateLivestreamVideo($resource_obj['config_id'], $course_id, $event_id);
            }
            \StudipLog::log('OC_REFRESH_SCHEDULED_EVENT', $termin_id, $course_id);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Gets the list of scheduling dates for a course to be displayed in the scheduling list in a course,
     * and return a livestream flag indicating if livestream is available in some date
     *
     * @param string $course_id course id
     * @param string $semester_filter semester id
     *
     * @return array the scheduling list to be displayed in a course and the livestream available flag
     */
    public static function getScheduleList($course_id, $semester_filter)
    {
        $allow_schedule_alternate = \Config::get()->OPENCAST_ALLOW_ALTERNATE_SCHEDULE;
        $livestream_available = false;

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
                        $title = count($titles) ? 'Themen: ' . my_substr(implode(', ', $titles), 0, 80) : '';
                    } else {
                        foreach ($issues as $is) {
                            $issue = new \Issue(['issue_id' => $is]);
                            $title = my_substr($issue->getTitle(), 0, 80);
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
                    if (!empty($scheduled['is_livestream'])) {
                        $status['info'] = 'LIVE';
                        $start = intVal($scheduled['start']);
                        $end = intVal($scheduled['end']);
                        $livestream_status = self::getLivestreamTimeStatus($start, $end);
                        if ($livestream_status == self::LIVESTREAM_STATUS_SCHEDULED) {
                            $status['title'] = _('Livestream ist bereits geplant.');
                            $status['referesh_at'] = $start;
                        } else if ($livestream_status == self::LIVESTREAM_STATUS_LIVE) {
                            $status['title'] = _('Livestream ist geplant, es wurde jedoch keine Veröffentlichung gefunden.');
                            if (in_array('engage-live', $events[$scheduled['event_id']]->publication_status)) {
                                $status['title'] = _('Livestream läuft gerade.');
                            }
                            $status['info_class'] = 'text-red';
                            $status['referesh_at'] = $end;
                        } else {
                            $status['title'] = _('Livestream beendet.');
                        }
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
                $allow_livestream = !empty($resource_obj['livestream_workflow_id']) ? true : false;
                $livestream_available |= $allow_livestream;
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
                                'info' => 'LIVE',
                                'title' => _('Livestream planen')
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

        return [
            'schedule_list' => $schedule_list,
            'livestream_available' => $livestream_available,
        ];
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

    /**
     * Adds or updates a resource and takes care of updating other parts
     *
     * @param string $resource_id id of resource
     * @param string $config_id id of server config
     * @param string $capture_agent name of capture agent
     * @param string $workflow_id name of workflow
     * @param string $livestream_workflow_id (optional) id of livestream workflow
     *
     * @return bool
     */
    public static function addUpdateResource($resource_id, $config_id, $capture_agent, $workflow_id, $livestream_workflow_id)
    {
        // We take the current resource obj to use its data for deleteEventForSeminar,
        // to make sure that server config id is correct!
        $ex_resource_obj = Resources::findByResource_id($resource_id);
        // We Update the resource first.
        $success = Resources::setResource($resource_id, $config_id, $capture_agent, $workflow_id, $livestream_workflow_id);
        // If is updated.
        /*
        if ($success) {
            // We update current scheduled events that uses this resource.
            if ($scheduled_recordings = ScheduledRecordings::getScheduleRecordingList($resource_id)) {
                foreach ($scheduled_recordings as $recording) {
                    // We try to update the those record as well.
                    $updated = self::updateEventForSeminar($recording['seminar_id'], $recording['date_id'], null, null, true);
                    // If update fails, we remove them!
                    if (!$updated && $ex_resource_obj) {
                        self::deleteEventForSeminar($recording['seminar_id'], $recording['date_id'], $ex_resource_obj['config_id']);
                    }
                }
            }
        }
        */
        return $success;
    }

    /**
     * Removes a resource and takes care of the cleaning other parts
     *
     * @param string $resource_id id of resource
     *
     * @return bool
     */
    public static function deleteResource($resource_id)
    {
        // We take the current resource obj to use its data for deleteEventForSeminar,
        // to make sure that server config id is correct!
        $ex_resource_obj = Resources::findByResource_id($resource_id);
        // Then we delete the resource.
        $success = Resources::removeResource($resource_id);
        // If the deletion succeed, then we perform the deleting of the scheduled recordings.
        if ($success) {
            $scheduled_recordings = ScheduledRecordings::getScheduleRecordingList($resource_id);
            if (!empty($scheduled_recordings) && !empty($ex_resource_obj)) {
                foreach ($scheduled_recordings as $recording) {
                    self::deleteEventForSeminar($recording['seminar_id'], $recording['date_id'], $ex_resource_obj['config_id']);
                }
            }
        }

        return $success;
    }

    /**
     * Send personal recording notifications to users for passed course
     *
     * @param string $course_id
     *
     * @return void
     */
    public static function sendRecordingNotifications($course_id)
    {
        $course = \Course::find($course_id);
        $members = $course->members;
        $users = [];

        foreach ($members as $member) {
            $users[] = $member->user_id;
        }

        $notification = sprintf(
            _('Die Veranstaltung "%s" wird für Sie mit Bild und Ton automatisiert aufgezeichnet.'),
            htmlReady($course->name)
        );

        $plugin = \PluginEngine::getPlugin('OpencastV3');
        $assetsUrl = rtrim($plugin->getPluginURL(), '/') . '/assets';
        $icon =  \Icon::create($assetsUrl . '/images/opencast-black.svg');

        \PersonalNotifications::add(
            $users,
            \PluginEngine::getURL('opencastv3', ['cid' => $course_id], 'course'),
            $notification,
            $course_id,
            $icon
        );
    }

    /**
     * Helper function that ensures only one course playlist has the flag for livestream or scheduled recordings.
     *
     * @param int $playlist_id the source playlist id
     * @param string $course_id course id
     * @param string $type the type of flag to change; values are ['livestreams', 'scheduled']
     *
     * @return bool the final success indicator.
     */
    public static function setScheduledRecordingsPlaylist($playlist_id, $course_id, $type)
    {
        try {
            $seminar_playlists = PlaylistSeminars::findBySQL('seminar_id = ?', [$course_id]);
            $column_to_update = $type == 'livestreams' ? 'contains_livestreams' : 'contains_scheduled';
            foreach ($seminar_playlists as $seminar_playlist) {
                $value = $seminar_playlist->playlist_id == $playlist_id ? true : false;
                $seminar_playlist->{$column_to_update} = $value;
                $seminar_playlist->store();
            }
        } catch (\Throwable $th) {
            return false;
        }
        return true;
    }

    /**
     * Creates or Updates the Video Object that holds the livestream event. The Playlist mapping also takes palce here.
     *
     * @param int $config_id opencast config id
     * @param string $course_id course id
     * @param string $event_id event id
     *
     * @return bool
     */
    public static function createOrUpdateLivestreamVideo($config_id, $course_id, $event_id)
    {
        global $user;
        $events_client = ApiEventsClient::getInstance($config_id);
        $params = ['withmetadata' => false, 'withscheduling' => true, 'withpublications' => true];
        $event = $events_client->getEpisode($event_id, $params);

        if ($event) {
            $video = Videos::findByEpisode($event_id);
            if (empty($video)) {
                $video = new Videos;
            }
            $publication = '';
            if (!empty($event->publications)) {
                $publication = json_encode($event->publications);
            }
            $video->setData([
                'episode'       => $event_id,
                'config_id'     => $config_id,
                'title'         => $event->title,
                'description'   => $event->description,
                'duration'      => $event->duration,
                'state'         => 'running',
		        'created'       => date('Y-m-d H:i:s', strtotime($event->created)),
		        'presenters'    => $event->creator,
                'available'     => true,
                'publication'   => $publication,
                'is_livestream' => true,
                // 'trashed'       => false
            ]);
            if (!$video->token) {
                $video->token = bin2hex(random_bytes(8));
            }
            $video->store();

            // add permissions to this video for current user
            if (!empty($user)) {
                $perm = VideosUserPerms::findOneBySQL('user_id = :user_id AND video_id = :video_id', [
                    ':user_id'  => $user->id,
                    ':video_id' => $video->id
                ]);

                if (empty($perm)) {
                    $perm = new VideosUserPerms();
                    $perm->user_id  = $user->id;
                    $perm->video_id = $video->id;
                    $perm->perm     = 'owner';
                    $perm->store();
                }
            }

            // Finding the livestream playlist.
            $playlist_id = null;
            $seminar_livestream_playlist = PlaylistSeminars::findOneBySQL('seminar_id = ? AND contains_livestreams = 1', [$course_id]);

            // Force create and get course default playlist. which then works as livestream playlist if nothing is set yet.
            if (empty($seminar_livestream_playlist)) {
                $default_playlist = Helpers::checkCoursePlaylist($course_id);
                $playlist_id = $default_playlist->id;
                // Make sure the livestream flag is set correctly!
                if (self::setScheduledRecordingsPlaylist($default_playlist->id, $course_id, 'livestream')) {
                    // Make sure the seminar_livestream_playlist is not empty.
                    $seminar_livestream_playlist = PlaylistSeminars::findOneBySQL('seminar_id = ? AND contains_livestreams = 1', [$course_id]);
                }
            } else {
                $playlist_id = $seminar_livestream_playlist->playlist_id;
            }

            // Add video into PlaylistVideos
            if (!is_null($playlist_id)) {
                $pvideo = PlaylistVideos::findOneBySQL('video_id = ? AND playlist_id = ?', [$video->id, $playlist_id]);

                if (empty($pvideo)) {
                    $pvideo = new PlaylistVideos();
                    $pvideo->video_id    = $video->id;
                    $pvideo->playlist_id = $playlist_id;
                    $pvideo->store();
                }
            }

            // Add Video into PlaylistSeminarVideos
            if (!empty($seminar_livestream_playlist)) {
                $psv = PlaylistSeminarVideos::findOneBySQL(
                    "LEFT JOIN oc_playlist_seminar AS ops ON ops.id = playlist_seminar_id
                    WHERE video_id = ?
                    AND playlist_id = ?
                    AND seminar_id = ?",
                    [$video->id, $playlist_id, $course_id]);
                if (empty($psv)) {
                    $psv = new PlaylistSeminarVideos();
                    $psv->setValue('playlist_seminar_id', $seminar_livestream_playlist->id);
                    $psv->setValue('video_id', $video->id);
                    $psv->setValue('visibility', 'visible');
                    $psv->setValue('visible_timestamp', date('Y-m-d H:i:s'));
                    $psv->store();
                }
            }
        }
    }

    /**
     * Removes the video from the list only when it is as livestream.
     *
     * @param string $event_id the event id
     */
    public static function removeLivestreamVideo($event_id)
    {
        Videos::deleteBySql('episode = ? AND is_livestream = 1', [$event_id]);
    }

    /**
     * Helper function to check what phase livestream is currently at.
     *
     * @param int $start the start timestamp of the scheduled livestream
     * @param int $end the end timestamp of the scheduled livestream
     *
     * @return string the livestream status
     */
    public static function getLivestreamTimeStatus(int $start, int $end): string
    {
        $now = time();
        if ($start > $now) {
            return self::LIVESTREAM_STATUS_SCHEDULED;
        } else if ($start < $now && $now < $end) {
            return self::LIVESTREAM_STATUS_LIVE;
        }
        return self::LIVESTREAM_STATUS_FINISHED;
    }
}
