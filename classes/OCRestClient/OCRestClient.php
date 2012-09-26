<?php
    /***
     * OCRestClient.php - The administarion of the opencast player
     * Copyright (c) 2011  André Klaßen
     *
     * This program is free software; you can redistribute it and/or
     * modify it under the terms of the GNU General Public License as
     * published by the Free Software Foundation; either version 2 of
     * the License, or (at your option) any later version.
     */

    class OCRestClient
    {
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
          * function getConfig  - retries configutation for a given REST-Service-Client
          *
          * @param string $service_type - client label
          *
          * @return array configuration for corresponding client
          *
          */
        function getConfig($service_type) {
            if(isset($service_type)) {
                $stmt = DBManager::get()->prepare("SELECT * FROM `oc_config` WHERE service_type = ?");
                $stmt->execute(array($service_type));
                return $stmt->fetch();
            } else {
                throw new Exception(_("Es wurde kein Servicetyp angegeben."));
            }
        }

        /**
         *  function setConfig - sets config into DB for given REST-Service-Client
         *
         *	@param string $service_type
         *	@param string $service_url
         *	@param string $service_user
         *  @param string $service_password
         */
        function setConfig($service_type, $service_url, $service_user, $service_password) {
            if(isset($service_type, $service_url, $service_user, $service_password)) {
                $stmt = DBManager::get()->prepare("REPLACE INTO `oc_config` (service_type, service_url, service_user, service_password) VALUES (?,?,?,?)");
                return $stmt->execute(array($service_type, $service_url, $service_user, $service_password));
            } else {
                throw new Exception(_('Die Konfigurationsparameter wurden nicht korrekt angegeben.'));
            }

        }

        /**
         *  function getJSON - performs a REST-Call and retrieves response in JSON
         */
        function getJSON($service_url) {
            if(isset($service_url) && self::checkService($service_url)) {
                curl_setopt($this->ochandler,CURLOPT_URL,$this->matterhorn_base_url.$service_url);
                $response = curl_exec($this->ochandler);
                $httpCode = curl_getinfo($this->ochandler, CURLINFO_HTTP_CODE);
                if ($httpCode == 404){
                    return false;
                } else {
                    return json_decode($response);
                }
            } else {
                throw new Exception(_("Es wurde keine Service URL angegben"));
            }

        }
        /**
         * function getJSON - performs a REST-Call and retrieves response in JSON
         */
        function getXML($service_url, $data = array(), $is_get = true) {
            if(isset($service_url) && self::checkService($service_url)) {
                curl_setopt($this->ochandler,CURLOPT_URL,$this->matterhorn_base_url.$service_url);
                if(!$is_get) {
                    curl_setopt($this->ochandler, CURLOPT_POST, 1);
                    if(!empty($data)) {
                        curl_setopt($this->ochandler, CURLOPT_POSTFIELDS, $data);
                    }
                }
                $response = curl_exec($this->ochandler);
                $httpCode = curl_getinfo($this->ochandler, CURLINFO_HTTP_CODE);
                if ($httpCode == 404){
                    return false;
                } else {
                    return $response;
                }
            } else {
                throw new Exception(_("Es wurde keine Service URL angegben"));
            }
        }

        /**
         * function checkService - checks the status of desired REST-Endpoint
         *
         *  @param string $service_url
         *
         *  @return boolean $status
         */
        static function checkService($service_url) {

          return true;
          /*if(fsockopen($service_url)) {
              return true;
          } else {
              return true; //throw new Exception(_("Es besteht momentan keine Verbindung zum gew�hlten Service -> .") . );
          }
          */
        }

    }
?>
