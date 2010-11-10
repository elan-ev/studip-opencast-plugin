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
          
          curl_setopt($this->ochandler, CURLOPT_USERPWD, $this->username.':'.$this->password);
          curl_setopt($this->ochandler, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST); 
          curl_setopt($this->ochandler, CURLOPT_HTTPHEADER, array('X-Requested-Auth: Digest'));
          curl_setopt($this->ochandler, CURLOPT_RETURNTRANSFER, true);
          
          
      }

      /**
       * 
       * TODO: Seperate Series Client form Search Client
       */
      
      function getAllSeries() {
          $rest_end_point = "/series/rest/all";
          curl_setopt($this->ochandler,CURLOPT_URL,$this->matterhorn_base_url.$rest_end_point);
          $response = curl_exec($this->ochandler);
          
          return simplexml_load_string($response);
          
      }
      
      function getSeries($series_id) {
          $rest_end_point = "/series/rest/series/";
          curl_setopt($this->ochandler,CURLOPT_URL,$this->matterhorn_base_url.$rest_end_point.$series_id);
          $response = curl_exec($this->ochandler);
          
          return simplexml_load_string($response);
      }
      
      function getEpisode($series_id) {
          $rest_end_point = "/search/rest/episode?id=";
          curl_setopt($this->ochandler,CURLOPT_URL,$this->matterhorn_base_url.$rest_end_point.$series_id);
          $response = curl_exec($this->ochandler);
          return simplexml_load_string($response);
      }
      
      /*************************************************************************
       *  static functions
       */
      function getConfig($id = '1') {
          $stmt = DBManager::get()->prepare("SELECT * FROM `oc_config` WHERE config_id = ?");
          
          $stmt->execute(array($id));
          return $stmt->fetch(); 
      }
      
        function setConfig($config_id, $series_url, $search_url, $user, $password) {
          $stmt = DBManager::get()->prepare("REPLACE INTO `oc_config` (config_id, series_url, search_url, user, password) VALUES (?,?,?,?,?)");
          
          return $stmt->execute(array($config_id, $series_url, $search_url, $user, $password));
      }
      
      
      static function storeAllSeries($series_id) {
        $stmt = DBManager::get()->prepare("REPLACE INTO 
                oc_series (series_id)
                VALUES (?)");
        
        return $stmt->execute(array($series_id));
      } 
  }