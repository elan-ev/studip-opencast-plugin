<?php
require_once "OCRestClient.php";

class IngestClient extends OCRestClient {
    function __construct() {
        if ($config = parent::getConfig('upload')) {
            parent::__construct($config['service_url'],
                                $config['service_user'],
                                $config['service_password']);
        } else {
            throw new Exception (_("Die Ingestservice Konfiguration wurde nicht im gültigen Format angegeben."));
        }
    }
    /**
     * Generate job ID -- for every new track upload job
     * 
     * @return boolean
     */
    function newJob($name, $size, $chunksize, $flavor, $mediapackage) {
        $data = array(
            'filename' => $name,
            'filesize' => $size,
            'chunksize' => $chunksize,
            'flavor' => $flavor,
            'mediapackage' => $mediapackage
        );
        if($response = self::getXML('newjob', $data, false)){
            return $response;
        } else return false;
    }
    
    function uploadChunk($jobId, $chunknumber, $filedata) {
        $option = array(
            'chunknumber' => $chunknumber,
            'filedata' => $filedata
        );
        if($response = self::getXML('job/'.$jobId, $data, false)){
            return $response;
        } else return false;
    }
}