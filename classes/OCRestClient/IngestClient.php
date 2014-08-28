<?php
    require_once "OCRestClient.php";
    class IngestClient extends OCRestClient
    {
        static $me;
        function __construct() {
            $this->serviceName = 'IngestClient';
            try {
                if ($config = parent::getConfig('ingest')) {
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
         *  createMediaPackage - Creates an empty media package
         *
         *  @return $mediapackage
         */
        function createMediaPackage() {
            $service_url = "/createMediaPackage";
            if($mediapackage = self::getXML($service_url)){
                return $mediapackage;
            } else return false;
        }
        
        /**
         *  addDCCatalog - Add a dublincore episode catalog to a given media package using an url
         *
         *  @param $mediapackage
         *  @param $dublincore
         *  @param $flavor
         *
         *  @return $mediapackage - the augmented mediapackage
         */
        function addDCCatalog($mediaPackage, $dublinCore, $flavor)
        {
            $service_url = "/addDCCatalog";
            $data = array('mediaPackage' =>  utf8_encode($mediaPackage),
                    'dublinCore' => $dublinCore,
                    'flavor' => $flavor);
            if($mediapackage = $this->getXML($service_url, $data, false)){
                return $mediapackage;
            } else return false;
        }
        
        /**
         *  ingest - Ingest the completed media package into the system, retrieving all URL-referenced files
         *
         *  @param $mediapackage
         *  @param $workFlowDefinitionID
         *
         *  @return $mediapackage 
         */
        function ingest($mediaPackage, $workFlowDefinitionID = 'full')
        {
            $service_url = "/ingest/".$workFlowDefinitionID;
            $data = array('mediaPackage' => $mediaPackage);
            if($mediapackage = $this->getXML($service_url, $data, false)){
                return $mediapackage;
            } else return false;
        }
    }
?>