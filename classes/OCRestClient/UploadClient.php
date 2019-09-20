<?php

use Opencast\Models\OCConfig;

class UploadClient extends OCRestClient {
    static $me;
    public $serviceName = 'Upload';

    function __construct($config_id = 1)
    {
        if ($config = OCConfig::getConfigForService('upload', $config_id)) {
            parent::__construct($config);
        } else {
            throw new Exception (_("Die Konfiguration wurde nicht korrekt angegeben"));
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

        if($response = $this->getXML($rest_end_point, $data, false)) {
            return $response;
        } else {
            return false;
        }
    }
    /**
     * upload one chunk
     */
    function uploadChunk($job_id, $chunknumber, $filedata)
    {
        $file = new CURLFile($filedata['name']);
        $file->setMimeType($filedata['mime']);
        $file->setPostFilename($filedata['postname']);

        $data = array(
            'chunknumber' => $chunknumber,
            'filedata' => $file
        );

        $rest_end_point = "/job/".$job_id;
        $uri = $rest_end_point;

        // setting up a curl-handler
        curl_setopt($this->ochandler,CURLOPT_URL,$this->base_url.$uri);
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
