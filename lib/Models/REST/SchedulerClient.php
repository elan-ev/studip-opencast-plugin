<?php

namespace Opencast\Models\REST;

use Opencast\Models\Config;
use Opencast\Models\OCScheduledRecordings;
use Opencast\Configuration;

class SchedulerClient extends RestClient
{
    public static $me;

    public function __construct($config_id = 1)
    {
        $this->serviceName = 'SchedulerClient';

        if ($config = Config::getConfigForService('recordings', $config_id)) {
            parent::__construct($config);
        } else {
            throw new \Exception ($this->serviceName . ': '
                . _('Die Opencast-Konfiguration wurde nicht korrekt angegeben'));
        }
    }

    /**
     * scheduleEventForSeminar - schedules an event
     * TODO: Fix agentparameter
     *
     * @param string $course_id - course identifier
     * @param string $resource_id - resource identifier
     * @param string $termin_id - termin identifier
     *
     * @return bool success or not
     */
    public function scheduleEventForSeminar($course_id, $resource_id, $publishLive, $termin_id)
    {
        $ingest_client = new IngestClient();
        $media_package = $ingest_client->createMediaPackage();
        $metadata      = self::createEventMetadata($course_id, $resource_id, $termin_id, null);
        $media_package = $ingest_client->addDCCatalog($media_package, $metadata['dublincore']);

        $result = $ingest_client->schedule($media_package, $metadata['workflow']);

        if ($result
            && $result[1] != 400
            && $result[1] != 409) {

            $xml = simplexml_load_string($media_package);
            OCModel::scheduleRecording($course_id, $resource_id, $termin_id, (string)$xml['id']);

            return true;
        } else {
            return false;
            // throw new Exception('Could not schedule: ' . $result[0]);
        }
    }


    /**
     * delelteEventForSeminar -  deletes a scheduled event
     *
     * @param string $course_id - course identifier
     * @param string $resource_id - resource identifier
     * @param string $date_id - termin identifier
     *
     * @return bool success or not
     */
    public function deleteEventForSeminar($course_id, $resource_id, $date_id)
    {
        $event_data = OCModel::checkScheduled($course_id, $resource_id, $date_id);
        $event_id   = $event_data[0]['event_id'];

        $result = $this->deleteEvent($event_id);

        // remove scheduled event from studip even though it isn't available on opencast
        if (in_array($result[1], [200, 204, 404])) {
            OCModel::unscheduleRecording($event_id);

            return true;
        } else {
            return false;
        }
    }

    /**
     * Deletes an event
     * 
     * @param string $event_id the event id
     * 
     * @return boolean success or not
     */
    public function deleteEvent($event_id)
    {
        $response = $this->opencastApi->recordings->deleteRecording($event_id);

        if ($response['code'] == 200) {
            return true;
        }
        return false;
    }

    /**
     * Updates an event
     *
     * @param string $course_id - course identifier
     * @param string $resource_id - resource identifier
     * @param string $termin_id - termin identifier
     *
     * @return bool success or not
     */
    public function updateEventForSeminar($course_id, $resource_id, $termin_id, $event_id)
    {
        $config_id      = Config::getConfigIdForCourse($course_id);
        $precise_config = Configuration::instance($config_id);

        // currently, we only update the start and the end time
        $event = OCScheduledRecordings::find($event_id);
        $date  = CourseDate::find($termin_id);

        if ($date->date > $event->start) {
            $event->start = $date->date;
            $event->store();
        }

        if ($date->end_time < $event->end) {
            $event->end = $date->end_time;
            $event->store();
        }

        $metadata = self::createEventMetadata($course_id, $resource_id, $termin_id, $event_id);

        $start = $event->start * 1000;
        $end = ($event->end - $precise_config['time_buffer_overlap'] ?: 0) * 1000;
        $agentparameters = $metadata['agentparameters'];
        $agent = $metadata['agent'];
        $response = $this->opencastApi->recordings->updateRecording(
            $event_id,
            $start,
            $end,
            $agent,
            '',
            '',
            '',
            $agentparameters
        );

        if ($response['code'] == 200) {
            return true;
        }
        return false;
    }

    /**
     * Creates event recording metadata
     * 
     * @param string $course_id course id
     * @param string $resource_id resource id
     * @param string $termin_id termin id
     * @param string $event_id event id
     * 
     * @return array event recording metadata
     */
    public static function createEventMetadata($course_id, $resource_id, $termin_id, $event_id)
    {
        $config = Config::getConfigForCourse($course_id);

        $dublincore = OCModel::createScheduleEventXML(
            $course_id, $resource_id, $termin_id, $event_id, $config['time_buffer_overlap']
        );

        $date = new SingleDate($termin_id);

        $issue_titles = [];
        $issues       = $date->getIssueIDs();

        if (is_array($issues)) {
            foreach ($issues as $is) {
                $issue = new Issue(['issue_id' => $is]);

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
            $course = new Seminar($course_id);
            $name   = $course->getName();
            $title  = $name . ' ' . sprintf(_('(%s)'), $date->getDatesExport());
        } else {
            $title = $issue_titles;
        }

		if (StudipVersion::newerThan('4.4')) {
        	$room = new Resource($resource_id);
        } else {
        	$room = ResourceObject::Factory($resource_id);
        }
        $cas  = OCModel::checkResource($resource_id);
        $ca   = $cas[0];

        $device   = $ca['capture_agent'];
        $workflow = $ca['workflow_id'];

        $ca_client    = CaptureAgentAdminClient::getInstance();
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
}
