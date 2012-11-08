<?php
require_once "OCRestClient.php";
// TODO: config in Datenbank


class UploadClient extends OCRestClient {
        
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
        // http_build_query content is not accepted by REST
        foreach($data as $key => $val) {
            $string[] = $key.'='.$val;
        }
        $string = implode('&', $string);
        $params = '?'.$string;
        return $response = self::getXML('/upload/newjob'.$params);
    }
    /**
     * upload one chunk
     */
    function uploadChunk($jobId, $chunknumber, $filedata) {
        $data = array(
            'chunknumber' => $chunknumber,
            'filedata' => $filedata
        );
        if($response = self::getXML('/upload/job/'.$jobId, $data, false, true)){
            return $response;
        } else return false;
    }
    /**
     * get State object 
     */
    function getState($jobID)
    {
        return self::getJSON('/upload/job/'.$jobID.'.json');
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