<?php
    require_once "OCRestClient.php";
    class SchedulerClient extends OCRestClient
    {
        static $me;
        public $serviceName = 'Scheduler';
        function __construct() {


            if ($config = parent::getConfig('schedule')) {
                parent::__construct($config['service_url'],
                                    $config['service_user'],
                                    $config['service_password']);
            } else {
                throw new Exception (_("Die Schedulerservice Konfiguration wurde nicht im gültigen Format angegeben."));
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

            $dublincore = utf8_encode(OCModel::createScheduleEventXML($course_id, $resource_id, $termin_id));

            $agentparameters = '#Capture Agent specific data
                                #Wed Apr 06 10:16:19 CEST 2011
                                event.title=Demotitle
                                event.location=testdevice
                                capture.device.id=testdevice';



            $post = array('dublincore' => $dublincore, 'agentparameters' => $agentparameters);


            $rest_end_point = "/recordings/";
            $uri = $rest_end_point;
            // setting up a curl-handler
            curl_setopt($this->ochandler,CURLOPT_URL,$this->matterhorn_base_url.$uri);
            curl_setopt($this->ochandler, CURLOPT_POST, true);
            curl_setopt($this->ochandler, CURLOPT_POSTFIELDS, $post);
            curl_setopt($this->ochandler, CURLOPT_HEADER, true);
            //TODO über REST Classe laufen lassen, getXML, getJSON...

            $response = curl_exec($this->ochandler);
            $httpCode = curl_getinfo($this->ochandler, CURLINFO_HTTP_CODE);

            $resArray = explode("\n", $response);

            if ($httpCode == 201){

                foreach($resArray as $resp) {
                    // THIS might be an pitfal. Keep an eye on futre oc releases...
                    $pttrn = '#Location: http:/'.$this->matterhorn_base_url.'/recordings/recordings/(.+?).xml#Uis';
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

            $rest_end_point = "/recordings/".$event['event_id'];
            $uri = $rest_end_point;

            // setting up a curl-handler
            curl_setopt($this->ochandler,CURLOPT_URL,$this->matterhorn_base_url.$uri);
            curl_setopt($this->ochandler,CURLOPT_CUSTOMREQUEST, "DELETE");
//TODO über REST Classe laufen lassen, getXML, getJSON...

            $response = curl_exec($this->ochandler);
            $httpCode = curl_getinfo($this->ochandler, CURLINFO_HTTP_CODE);


            if ($httpCode == 200){
                $event_id = $event['event_id'];
                OCModel::unscheduleRecording($event_id);

                return true;
            } else {
                return false;
            }
        }
    }
?>