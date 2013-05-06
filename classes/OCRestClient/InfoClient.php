<?php
    require_once "OCRestClient.php";
    class InfoClient extends OCRestClient
    {
        static $me;
        function __construct() {
            if ($config = parent::getConfig('info')) {
                parent::__construct($config['service_url'],
                                    $config['service_user'],
                                    $config['service_password']);
            } else {
                throw new Exception (_("Die Konfiguration wurde nicht korrekt angegeben"));
            }
        }
        
        /**
         * getComponents() - retrieves episode system components from conntected Opencast-Matterhorn Core
         *
         *  @return array response of components
         */
        function getRESTComponents() {

            $service_url = "/components.json";

            if($components = $this->getJSON($service_url)){
                return $components->rest;
            } else {
                return false;
            }
        }
    
    
    }

    
?>