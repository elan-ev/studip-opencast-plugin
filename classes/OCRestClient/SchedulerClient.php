<?php
    require_once "OCRestClient.php";
    require_once "CaptureAgentAdminClient.php";
    class SchedulerClient extends OCRestClient
    {
        static $me;
        function __construct($config_id = 1) {
            $this->serviceName = 'SchedulerClient';
            try {
                if ($config = parent::getConfig('recordings', $config_id)) {
                    parent::__construct($config['service_url'],
                                        $config['service_user'],
                                        $config['service_password']);
                } else {
                    throw new Exception (_("Die Konfiguration wurde nicht korrekt angegeben"));
                }
            } catch(Exception $e) {

            }
        }


        /**
         * scheduleEventForSeminar - schedules an event
         * TODO: Fix agentparameter
         * @param string $course_id  - course identifier
         * @param string $resource_id  - resource identifier
         * @param string $termin_id  - termin identifier
         * @return bool success or not
         */
        function scheduleEventForSeminar($course_id, $resource_id, $termin_id) {

            $post = self::createEventMetadata($course_id, $resource_id, $termin_id);
            $rest_end_point = "/";
            $uri = $rest_end_point;
            // setting up a curl-handler
            curl_setopt($this->ochandler,CURLOPT_URL,$this->matterhorn_base_url.$uri);
            curl_setopt($this->ochandler, CURLOPT_POST, true);
            curl_setopt($this->ochandler, CURLOPT_POSTFIELDS, $post);
            curl_setopt($this->ochandler, CURLOPT_HEADER, true);
            //TODO über REST Klasse laufen lassen, getXML, getJSON...

            $response = curl_exec($this->ochandler);
            $httpCode = curl_getinfo($this->ochandler, CURLINFO_HTTP_CODE);
            $resArray = explode("\n", $response);

            if ($httpCode == 201){
                $location = parse_url($this->matterhorn_base_url);
                $pttrn = '#Location: http:/.*/recordings/(.+?).xml#Uis';

                foreach($resArray as $resp) {
                    // THIS could be changed. Keep an eye on future oc releases...
                    if(preg_match($pttrn, $resp, $matches)) {
                        $eventid = $matches[1];
                    }
                }

                OCModel::scheduleRecording($course_id, $resource_id, $termin_id, $eventid);

                return true;
            } else {
                return false;
            }
        }


        /**
         * delelteEventForSeminar -  deletes a scheduled event
         *
         * @param string $course_id  - course identifier
         * @param string $resource_id  - resource identifier
         * @param string $date_id  - termin identifier
         * @return bool success or not
         */
        function deleteEventForSeminar($course_id, $resource_id, $date_id) {



            $event_data = OCModel::checkScheduled($course_id, $resource_id, $date_id);
            $event = $event_data[0];

            $rest_end_point = "/".$event['event_id'];
            $uri = $rest_end_point;

            // setting up a curl-handler
            $location = parse_url($this->matterhorn_base_url);
            curl_setopt($this->ochandler,CURLOPT_URL,$location['path'].$uri);
            curl_setopt($this->ochandler,CURLOPT_CUSTOMREQUEST, "DELETE");
            //TODO über REST Klasse laufen lassen, getXML, getJSON...

            $response = curl_exec($this->ochandler);
            $httpCode = curl_getinfo($this->ochandler, CURLINFO_HTTP_CODE);


            // remove scheduled event from studip even though it isn't available on opencast
            if ($httpCode == 200 || $httpCode == 404){
                $event_id = $event['event_id'];
                OCModel::unscheduleRecording($event_id);

                return true;
            } else {
                return false;
            }
        }

        /**
         * updateEventForSeminar - updates an event
         * TODO: Implement put route
         * @param string $course_id  - course identifier
         * @param string $resource_id  - resource identifier
         * @param string $termin_id  - termin identifier
         * @return bool success or not
         */
        function updateEventForSeminar($course_id, $resource_id, $termin_id, $event_id) {

            $post = self::createEventMetadata($course_id, $resource_id, $termin_id);

            $rest_end_point = "/";
            $uri = $rest_end_point;
            // setting up a curl-handler
            $location = parse_url($this->matterhorn_base_url);
            curl_setopt($this->ochandler,CURLOPT_URL,$location['path'].$uri.$event_id);
            curl_setopt($this->ochandler, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($this->ochandler, CURLOPT_POSTFIELDS, $post);
            curl_setopt($this->ochandler, CURLOPT_HEADER, true);
            //TODO über REST Klasse laufen lassen, getXML, getJSON...

            $response = curl_exec($this->ochandler);
            $httpCode = curl_getinfo($this->ochandler, CURLINFO_HTTP_CODE);
            $resArray = explode("\n", $response);
            if (in_array($httpCode, array(201,200))){
                return true;
            } else {
                return false;
            }
        }


        static function createEventMetadata($course_id, $resource_id, $termin_id) {
            $dublincore = studip_utf8encode(OCModel::createScheduleEventXML($course_id, $resource_id, $termin_id));


            $date = new SingleDate($termin_id);
            $start_time = date('D M d H:i:s e Y', $date->getStartTime());

            $issue_titles = array();
            $issues = $date->getIssueIDs();

            if (is_array($issues)) {
                foreach ($issues as $is) {
                    $issue = new Issue(array('issue_id' => $is));

                    if (sizeof($issues) > 1) {
                        $issue_titles[] =  my_substr(kill_format($issue->getTitle()), 0 ,80 );
                    } else {
                        $issue_titles =  my_substr(kill_format($issue->getTitle()), 0 ,80 );
                    }
                }

                if (is_array($issue_titles)) {
                    $issue_titles = _("Themen: ") . my_substr(implode(', ', $issue_titles), 0 ,80 );
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
            }
            else $workflow = $ca['workflow_id'];

            $ca_client = CaptureAgentAdminClient::getInstance();
            $device_names = '';
            $capabilities = $ca_client->getCaptureAgentCapabilities($ca['capture_agent']);

            if (isset($capabilities)) {
                foreach($capabilities as $capability) {
                    if($capability->key == 'capture.device.names') {
                        $device_names = $capability->value;
                    }
                }
            }

            $agentparameters = '#Capture Agent specific data
                                #'. $start_time .'
                                event.title=' . $title .'
                                event.location=' . $room->name . '
                                capture.device.id=' . $device . '
                                capture.device.names=' . $device_names .'
                                org.opencastproject.workflow.definition=' . $workflow;

            // uncomment if further parametes should be set like e.g. ncast definitions etc
            //$agentparameters .= in_array($device, words('ca-01-e01 ca-01-b01')) ? '
            //                    org.opencastproject.workflow.definition=ncast' : '';

            return array('dublincore' => $dublincore, 'agentparameters' => $agentparameters);

        }
    }
