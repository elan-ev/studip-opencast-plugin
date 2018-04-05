<?php
require_once "OCRestClient.php";
require_once "CaptureAgentAdminClient.php";

class SchedulerClient extends OCRestClient
{
    static $me;

    function __construct($config_id = 1)
    {
        $this->serviceName = 'SchedulerClient';
            if ($config = parent::getConfig('recordings', $config_id)) {
                parent::__construct($config['service_url'],
                    $config['service_user'],
                    $config['service_password']);
            } else {
                throw new Exception (_("Die Konfiguration wurde nicht korrekt angegeben"));
            }
    }

    /**
     * scheduleEventForSeminar - schedules an event
     * TODO: Fix agentparameter
     *
     * @param string $course_id   - course identifier
     * @param string $resource_id - resource identifier
     * @param string $termin_id   - termin identifier
     *
     * @return bool success or not
     */
    function scheduleEventForSeminar($course_id, $resource_id, $termin_id)
    {
        $ingest_client = new IngestClient();

        $media_package = $ingest_client->createMediaPackage();

        $metadata = self::createEventMetadata($course_id, $resource_id, $termin_id);
        $media_package = $ingest_client->addDCCatalog($media_package, $metadata['dublincore']);

        $result = $ingest_client->schedule($media_package, $metadata['device_capabilities'], $metadata['workflow']);

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
     * @param string $course_id   - course identifier
     * @param string $resource_id - resource identifier
     * @param string $date_id     - termin identifier
     *
     * @return bool success or not
     */
    function deleteEventForSeminar($course_id, $resource_id, $date_id)
    {
        $event_data = OCModel::checkScheduled($course_id, $resource_id, $date_id);
        $event = $event_data[0];
        $event_id = $event['event_id'];


        curl_setopt($this->ochandler, CURLOPT_CUSTOMREQUEST, 'DELETE');

        $result = $this->getJSON('/'. $event_id, [], false, true);

        // remove scheduled event from studip even though it isn't available on opencast
        if (in_array($result[1], array(200, 204, 404))) {
            OCModel::unscheduleRecording($event_id);

            return true;
        } else {
            return false;
        }
    }

    /**
     * updateEventForSeminar - updates an event
     * TODO: Implement put route
     *
     * @param string $course_id   - course identifier
     * @param string $resource_id - resource identifier
     * @param string $termin_id   - termin identifier
     *
     * @return bool success or not
     */
    function updateEventForSeminar($course_id, $resource_id, $termin_id, $event_id)
    {
        // currently, we only update the start and the end time
        $date = new SingleDate($termin_id);

        $post = array(
            'start' => $date->getStartTime() * 1000,
            'end'   => $date->getEndTime() * 1000,
        );

        curl_setopt($this->ochandler, CURLOPT_CUSTOMREQUEST, "PUT");

        $result = $this->getJSON("/$event_id", $post, false, true);

        if (in_array($result[1], array(201, 200))) {
            return true;
        } else {
            return false;
        }
    }


    static function createEventMetadata($course_id, $resource_id, $termin_id)
    {
        $dublincore = studip_utf8encode(OCModel::createScheduleEventXML($course_id, $resource_id, $termin_id));

        $date = new SingleDate($termin_id);
        $start_time = date('D M d H:i:s e Y', $date->getStartTime());

        $issue_titles = array();
        $issues = $date->getIssueIDs();

        if (is_array($issues)) {
            foreach ($issues as $is) {
                $issue = new Issue(array('issue_id' => $is));

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
            $name = $course->getName();
            $title = $name . ' ' . sprintf(_('(%s)'), $date->getDatesExport());
        } else {
            $title = $issue_titles;
        }

        $room = ResourceObject::Factory($resource_id);
        $cas = OCModel::checkResource($resource_id);
        $ca = $cas[0];
        $device = $ca['capture_agent'];

        $custom_workflow = OCSeriesModel::getWorkflowForEvent($course_id, $termin_id);

        if ($custom_workflow) {
            $workflow = $custom_workflow['workflow_id'];
        } else $workflow = $ca['workflow_id'];

        $ca_client = CaptureAgentAdminClient::getInstance();
        $device_names = '';
        $capabilities = $ca_client->getCaptureAgentCapabilities($ca['capture_agent']);

        if (isset($capabilities)) {
            foreach ($capabilities as $capability) {
                if ($capability->key == 'capture.device.names') {
                    $device_names = $capability->value;
                }
            }
        }

        $agentparameters = '#Capture Agent specific data
                                #' . $start_time . '
                                event.title=' . $title . '
                                event.location=' . $room->name . '
                                capture.device.id=' . $device . '
                                capture.device.names=' . $device_names . '
                                org.opencastproject.workflow.definition=' . $workflow;

        // uncomment if further parametes should be set like e.g. ncast definitions etc
        //$agentparameters .= in_array($device, words('ca-01-e01 ca-01-b01')) ? '
        //                    org.opencastproject.workflow.definition=ncast' : '';

        return array('device_capabilities'=>$device_names,'dublincore' => $dublincore, 'agentparameters' => $agentparameters, 'workflow' => $workflow);

    }
}
