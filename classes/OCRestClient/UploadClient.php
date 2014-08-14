<?php
require_once "OCRestClient.php";
// TODO: config in Datenbank


class UploadClient extends OCRestClient {
    static $me;
    public $serviceName = 'Upload';
        
    function __construct() {
        if ($config = parent::getConfig('upload')) {
            parent::__construct($config['service_url'],
                                $config['service_user'],
                                $config['service_password']);
        } else {
            throw new Exception (_("Die Uploadservice Konfiguration wurde nicht im gültigen Format angegeben."));
        }
    }

    /**
     * Generate job ID -- for every new track upload job
     * 
     * @return boolean
     */
    function newJob($name, $size, $chunksize, $flavor, $mediaPackage) {
        $data = array(
            'filename' => urlencode($name),
            'filesize' => $size,
            'chunksize' =>  $chunksize,
            'flavor' => urlencode($flavor),
            'mediapackage' => urlencode($mediaPackage)
        );
        $rest_end_point = "/newjob";
        $uri = $rest_end_point;
        
        // setting up a curl-handler
        curl_setopt($this->ochandler,CURLOPT_URL,$this->matterhorn_base_url.$uri);
        curl_setopt($this->ochandler, CURLOPT_POST, true);
        curl_setopt($this->ochandler, CURLOPT_POSTFIELDS, $data);
        curl_setopt($this->ochandler, CURLOPT_HEADER, false); // we don't need that kind of information now
        //TODO über REST Klasse laufen lassen, getXML, getJSON...

        $response = curl_exec($this->ochandler);
        $httpCode = curl_getinfo($this->ochandler, CURLINFO_HTTP_CODE);
        if ($httpCode == 200 && isset($response)){
            return $response;
        } else {
            return false;
        }
    }
    /**
     * upload one chunk
     */
    function uploadChunk($job_id, $chunknumber, $filedata) {

        $data = array(
            'chunknumber' => $chunknumber,
            'filedata' => '@'.$filedata//$filedata
        );
        
        $rest_end_point = "/job/".$job_id;
        $uri = $rest_end_point;
        
        // setting up a curl-handler
        curl_setopt($this->ochandler,CURLOPT_URL,$this->matterhorn_base_url.$uri);
        curl_setopt($this->ochandler, CURLOPT_POST, true);
        curl_setopt($this->ochandler, CURLOPT_POSTFIELDS, $data);
        curl_setopt($this->ochandler, CURLOPT_HTTPHEADER, array('Content-Type: multipart/form-data'));
        curl_setopt($this->ochandler, CURLOPT_ENCODING, "UTF-8");

        $response = curl_exec($this->ochandler);
        $httpCode = curl_getinfo($this->ochandler, CURLINFO_HTTP_CODE);
        $res = array();
        $res[] = $httpCode;
        $res[] = $response;
        if ($httpCode == 200 && isset($response)){
            return $res;
        } else {
            return false;
        }
    }
    /**
     * get State object 
     */
    function getState($jobID)
    {
        return $this->getJSON('/job/'.$jobID.'.json');
    }
    /**
     * check if state is $state
     */
    function checkState($state, $jobID) {
        if($response = $this->getState($jobID)) {
            return ($state == $response->uploadjob->state);
        } else return false;
    }
    /**
     * check if fileupload is in progress
     */
    function isInProgress($jobID)
    {
        return $this->checkState('INPROGRESS', $jobID);
    }
    /**
     * check if file upload is complete
     */
    function isComplete($jobID)
    {
        return $this->checkState('COMPLETE', $jobID);
    }
    /**
     * check if the chunk is the last
     */
    function isLastChunk($jobID)
    {
        $state = $this->getState($jobID);
        $ch = 'chunks-total';
        $ch2 = 'current-chunk';
        $numChunks = $state->uploadjob->$ch;
        $curChunk = $state->uploadjob->$ch2->number + 1;
        return ($numChunks == $curChunk);
     }
     public function getTrackURI($jobID)
     {
         $state = $this->getState($jobID);
         return $state->uploadjob->payload->mediapackage->media->track->url;
     }
   
    function addTrack($mediapackage, $flavor) {
        return true;
    }
}