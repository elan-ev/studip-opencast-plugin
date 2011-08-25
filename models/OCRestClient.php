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
