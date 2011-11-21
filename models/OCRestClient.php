<?php
  class OCRestClient {
      
      protected $matterhorn_base_url;
      protected $username;
      protected $password;
      
      function __construct($matterhorn_base_url = null, $username = null, $password = null){
          $this->matterhorn_base_url = $matterhorn_base_url;
          $this->username = $username;
          $this->password = $password;
          
          // setting up a curl-handler
          $this->ochandler = curl_init();
          curl_setopt($this->ochandler, CURLOPT_RETURNTRANSFER, 1);
          curl_setopt($this->ochandler, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
          curl_setopt($this->ochandler, CURLOPT_USERPWD, 'matterhorn_system_account'.':'.'CHANGE_ME');
          curl_setopt($this->ochandler, CURLOPT_ENCODING, "ISO-8859-1");
          curl_setopt($this->ochandler, CURLOPT_HTTPHEADER, array("X-Requested-Auth: Digest"));




      }

      /**
       * 
       * TODO: Seperate Series Client form Search Client
       */
      
      function getAllSeries() {
       
          $rest_end_point = "/series/all.json";
          curl_setopt($this->ochandler,CURLOPT_URL,$this->matterhorn_base_url.$rest_end_point);
          $response = curl_exec($this->ochandler);

          $httpCode = curl_getinfo($this->ochandler, CURLINFO_HTTP_CODE);
          if ($httpCode == 404){
              return false;
          } else {
               return json_decode($response);
          }
      }
      
      function getSeries($series_id) {
          $rest_end_point = "/series/";
          curl_setopt($this->ochandler,CURLOPT_URL,$this->matterhorn_base_url.$rest_end_point.$series_id.'.json');
          $response = curl_exec($this->ochandler);
          $httpCode = curl_getinfo($this->ochandler, CURLINFO_HTTP_CODE);
          if ($httpCode == 404){
              return false;
          } else {
               return json_decode($response);
          }
      }
      
      function getEpisode($series_id) {
          $rest_end_point = "/search/series.json?id=";
          curl_setopt($this->ochandler,CURLOPT_URL,$this->matterhorn_base_url.$rest_end_point.$series_id.'&episodes=true&series=true');
          $response = curl_exec($this->ochandler);
          $httpCode = curl_getinfo($this->ochandler, CURLINFO_HTTP_CODE);
          if ($httpCode == 404){
              return false;
          } else {
               return json_decode($response);
          }
      }

      function getAllCaptureAgents() {
          // URL for Matterhorn 1.1
          $rest_end_point = "/capture-admin/agents";
          // URL for Matterhorn 1.2
          //$rest_end_point = "/capture-admin/agents.json";
          curl_setopt($this->ochandler,CURLOPT_URL,$this->matterhorn_base_url.$rest_end_point);
          $response = curl_exec($this->ochandler);
          $httpCode = curl_getinfo($this->ochandler, CURLINFO_HTTP_CODE);
          if ($httpCode == 404){
              return false;
          } else {

              // deal with NS struggle of Matterhorn 1.1 since we cannot deal with json responses there...
              $needle = array('<ns1:agent-state-updates xmlns:ns1="http://capture.admin.opencastproject.org">',
                              '<ns1:agent-state-update xmlns:ns1="http://capture.admin.opencastproject.org">',
                              '</ns1:agent-state-update>',
                              '</ns1:agent-state-updates>');

              $replacements = array('<agent-state-updates>','<agent-state-update>','</agent-state-update>','</agent-state-updates>');
              $xml = simplexml_load_string(str_replace($needle, $replacements, $response));


              $json = json_encode($xml);


              return json_decode($json,TRUE);
          }
      }





      function createSeriesForSeminar($course_id) {


        $xml = utf8_encode(OCModel::creatSeriesXML($course_id));

        $post = array('series' => $xml);


        $rest_end_point = "/series/?_method=put&";
        $uri = $rest_end_point;
        // setting up a curl-handler
        curl_setopt($this->ochandler,CURLOPT_URL,$this->matterhorn_base_url.$uri);
        curl_setopt($this->ochandler, CURLOPT_POST, true);
        curl_setopt($this->ochandler, CURLOPT_POSTFIELDS, $post);


        $response = curl_exec($this->ochandler);
        $httpCode = curl_getinfo($this->ochandler, CURLINFO_HTTP_CODE);

        if ($httpCode == 201){

            $new_series = json_decode($response);
            $series_id = $new_series->series->id;
            OCModel::setSeriesforCourse($course_id, $series_id, 'visible', 1);

            return true;
        } else {
            return false;
        }
      }


      function scheduleEventForSeminar($course_id, $resource_id, $termin_id) {

        $xml = utf8_encode(OCModel::createScheduleEventXML($course_id, $resource_id, $termin_id));
        $post = array('event' => $xml);


        $rest_end_point = "/scheduler/?_method=put&";
        $uri = $rest_end_point;
        // setting up a curl-handler
        curl_setopt($this->ochandler,CURLOPT_URL,$this->matterhorn_base_url.$uri);
        curl_setopt($this->ochandler, CURLOPT_POST, true);
        curl_setopt($this->ochandler, CURLOPT_POSTFIELDS, $post);


        $response = curl_exec($this->ochandler);
        $httpCode = curl_getinfo($this->ochandler, CURLINFO_HTTP_CODE);

        if ($httpCode == 201){

            $event = simplexml_load_string($response);
           
            $event_id = $new_series->series->id;
            OCModel::scheduleRecording($course_id, $resource_id, $termin_id, $event['id']);

            return true;
        } else {
            return false;
        }
      }

      function deleteEventForSeminar($course_id, $resource_id, $date_id) {


        //$xml = utf8_encode(OCModel::createScheduleEventXML($course_id, $resource_id, $termin_id));
        //$post = array('eventId' => $xml);

        $event_data = OCModel::checkScheduled($course_id, $resource_id, $date_id);
        $event = $event_data[0];

        //$post =  array('eventId' => $event['event_id']);

        ///$rest_end_point = "/scheduler/?_method=delete&";
        $rest_end_point = "/scheduler/".$event['event_id']. "/?_method=delete" ;
        $uri = $rest_end_point;
        // setting up a curl-handler
        curl_setopt($this->ochandler,CURLOPT_URL,$this->matterhorn_base_url.$uri);

        $response = curl_exec($this->ochandler);
        $httpCode = curl_getinfo($this->ochandler, CURLINFO_HTTP_CODE);

        if ($httpCode == 204){
            $event_id = $event['event_id'];
            OCModel::unscheduleRecording($event_id);

            return true;
        } else {
            return false;
        }
      }


      function updateEventForSeminar($course_id, $resource_id, $date_id) {
        $xml = utf8_encode(OCModel::createScheduleEventXML($course_id, $resource_id, $termin_id));
        $post = array('event' => $xml);
        echo "<pre>";
        echo $xml;
        echo "</pre>";
        die;

        $event_data = OCModel::checkScheduled($course_id, $resource_id, $date_id);
        $event = $event_data[0];



        $rest_end_point = "/scheduler/".$event['event_id']. "?_method=post&";
        $uri = $rest_end_point;
        // setting up a curl-handler
        curl_setopt($this->ochandler,CURLOPT_URL,$this->matterhorn_base_url.$uri);
        curl_setopt($this->ochandler, CURLOPT_POST, true);
        curl_setopt($this->ochandler, CURLOPT_POSTFIELDS, $post);


        $response = curl_exec($this->ochandler);
        $httpCode = curl_getinfo($this->ochandler, CURLINFO_HTTP_CODE);

        if ($httpCode == 204){

            $event = simplexml_load_string($response);

            $event_id = $new_series->series->id;
            OCModel::updateRecording($course_id, $resource_id, $termin_id, $event['id']);

            return true;
        } else {
            return false;
        }
      }


      
      /*************************************************************************
       *  static functions
       */
      function getConfig($service_type = 'search') {
          $stmt = DBManager::get()->prepare("SELECT * FROM `oc_config` WHERE service_type = ?");
          
          $stmt->execute(array($service_type));
          return $stmt->fetch(); 
      }
      
        function setConfig($service_type, $service_url, $service_user, $service_password) {

          $stmt = DBManager::get()->prepare("REPLACE INTO `oc_config` (service_type, service_url, service_user, service_password) VALUES (?,?,?,?)");
          
          return $stmt->execute(array($service_type, $service_url, $service_user, $service_password));
      }
      
      
      static function storeAllSeries($series_id) {

        // 
        $stmt = DBManager::get()->prepare("SELECT * FROM `oc_series` WHERE series_id = ?");
        $stmt->execute(array($series_id));
        if(!$stmt->fetch()) {
            $stmt = DBManager::get()->prepare("REPLACE INTO 
                    oc_series (series_id)
                    VALUES (?)");
            return $stmt->execute(array($series_id));
        }
        else return false;

      } 
  }
