<?php
    require_once "OCRestClient.php";
    class IngestClient extends OCRestClient
    {
        function __construct() {

            if ($config = parent::getConfig('ingest')) {
                parent::__construct($config['service_url'],
                                    $config['service_user'],
                                    $config['service_password']);
            } else {
                throw new Exception (_("Die Ingestservice Konfiguration wurde nicht im gültigen Format angegeben."));
            }
        }

        /**
         *  getAllSeries() - retrieves all series from conntected Opencast-Matterhorn Core
         *
         *  @return array response all series
         */
        function createMediaPackage() {
            $service_url = "/createMediaPackage";
            if($response = self::getXML($service_url)){
                return $response;
            } else return false;
        }


        // other functions

        function getUploadFrame() {


            $frame = '<iframe name="fileChooserAjax" id="fileChooserAjax" frameborder="0" scrolling="no" '
                   .    'src="http://' . $this->matterhorn_base_url .'/ingest/filechooser-local.html">'
                   . '</iframe>';

            return $frame;

        }


    }


?>