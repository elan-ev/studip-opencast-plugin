<?php

class OCUploadFile {
    private $name = '';
    private $size = 0;
    private $type = '';
    private $chunks = array();
    private $chunk = 0;
    private $isNew = true;
    private $mediaPackage = '';
    private $jobID = '';
    private $flavor = 'presenter/source'; //->Dozent 'presentation/source'->VGA.Aufz.
    private $seriesDC = '';
    private $ACL;
    private $episodeData;


    public function __construct($name, $size, $type, $error, $index = null)
    {
        $this->name = $name;
        if(!empty($_SESSION['opencast']['files'][$name]) 
                && $this->getSess('time') + 10 >= time()) {
            $this->size = $this->getSess('size');
            $this->type = $this->getSess('type');
            $this->chunks = $this->getSess('chunks');
            $this->chunk = $this->getSess('chunk');
            $this->chunk++;
            $this->setSess('chunk', $this->chunk);
            $this->mediaPackage = $this->getSess('mediaPackage');
            $this->jobID = $this->getSess('jobID');
            $this->isNew = false;
            $this->seriesDC = $this->getSess('seriesDC');
            $this->ACL = $this->getSess('ACL');
            $this->episodeData = $this->getSess('episodeData');
        } else { //new upload
            //reset session values if time condition is violated
            $this->setSess('name', $name);
            $this->setSess('size', $size);
            $this->setSess('type', $type);
            $this->setSess('mediaPackage', '');
            $this->setSess('chunks', array());
            $this->setSess('chunk', 0);
            $this->setSess('jobID', '');
            
            $this->size = $size;
            $this->type = $type;
            $this->chunk = 0;
            $this->isNew = true;
        }
        $this->setSess('time', time()); //set last request timestamp
        $this->chunks[$this->chunk] = array(
            'status' => 'sip',
            'path' => '',
            'errorMSG' => ''
        );
    }
    public function __destruct()
    {
        if(is_file($this->chunks[$this->chunk]['path'])) {
            if(unlink($this->chunks[$this->chunk]['path'])) {
                $this->chunks[$this->chunk]['path'] = '';
            }
            $this->chunks[$this->chunk]['status'] = 'deleted';
        }
        $_SESSION['opencast']['files'][$this->name]['chunks'][$this->chunk] = 
                $this->chunks[$this->chunk];
    }
            
    
    /**
     * set path for current chunk. 
     */
    public function setChunkPath($path, $chunk = null) {
        if(is_null($chunk)) {
            $chunk = $this->chunk;
        }
        $this->chunks[$this->chunk]['path'] = $path;
    }
    public function getChunkPath($chunk = null) {
        if(is_null($chunk)) {
            $chunk = $this->chunk;
        }
        return $this->chunks[$this->chunk]['path'];
    }
    public function setChunkStatus($data, $chunk = null) {
        if(is_null($chunk)) {
            $chunk = $this->chunk;
        }
        $this->chunks[$this->chunk]['status'] = $data;
    }
    public function setChunkError($data, $chunk = null) {
        if(is_null($chunk)) {
            $chunk = $this->chunk;
        }
        $this->chunks[$this->chunk]['status'] = 'error';
        $this->chunks[$this->chunk]['errorMSG'] = $data;
    }
    public function getChunkError($chunk = null) {
        if(is_null($chunk)) {
            $chunk = $this->chunk;
        }
        return $this->chunks[$chunk]['errorMSG'];
    } 
    public function getChunkStatus($chunk = null) {
        if(is_null($chunk)) {
            $chunk = $this->chunk;
        }
        return $this->chunks[$chunk]['status'];
    }
    public function getChunk()
    {
        return $this->chunk;
    }

    /**
     * creates local file from php://input
     */
    public function createChunkFile()
    {
        $input = fopen('php://input', 'r');
        $path = tempnam($TMP_PATH, 'opencast_');
        file_put_contents($path, $input);
        $this->setChunkPath($path);
        return true;
    }
    /**
     * set session value
     */
    private function getSess($data) 
    {
        return $_SESSION['opencast']['files'][$this->name][$data];
    }
            
    /**
     * get session value
     */
    private function setSess($name, $data) 
    {
        return $_SESSION['opencast']['files'][$this->name][$name] = $data;
    }
    public function getMediaPackage() 
    {
        return $this->mediaPackage;
    }
    public function setMediaPackage($data) 
    {
        $this->mediaPackage = $data;
        $this->setSess('mediaPackage', $data);
    }
    public function setJobID($data) 
    {
        $this->jobID = $data;
        $this->setSess('jobID', $data);
    }
    public function setEpisodeData($data)
    {
        $this->episodeData = $data;
        $this->setSess('episodeData', $data);
    }
    public function getEpisodeDC()
    {
        //TODO: function steht so ähnlich auch in OCModel sollte vereinheitlicht werden 
        $data = $this->episodeData;
        $content = array();
        
        foreach($data as $key => $val)
        {
            $content[] = '<dcterms:'.$key.' xmlns="">'.$val.'</dcterms:'.$key.'>';
            
        }
        $str = '<?xml version="1.0"?>'
                .'<dublincore '
                .'xmlns="http://www.opencastproject.org/xsd/1.0/dublincore/" '
                .'xmlns:dcterms="http://purl.org/dc/terms/" '
                .'xmlns:dc="http://purl.org/dc/elements/1.1/" '
                .'xmlns:oc="http://www.opencastproject.org/matterhorn">'
                .  implode('', $content)
                .'</dublincore>';
       
        return $str;
    }
    public function getJobID() 
    {
        if(!empty($this->jobID)) {
            return $this->jobID;
        } else return false;
    }
    public function getName() 
    {
        return $this->name;
    }
    public function getSize() 
    {
        return $this->size;
    }
    public function getFlavor()
    {
        return $this->flavor;
    }
    public function isInProgress()
    {
        return $this->checkState('INPROGRESS', $jobID);
    }
    public function isComplete()
    {
        return $this->checkState('COMPLETE', $jobID);
    }
    public function checkState($state) {
        if($response = self::getJSON('/upload/job/'.$jobID.'.json')) {
            $this->state = $response;
            return ($state == $response->uploadjob->state);
        } else return false;
    }
    public function isNew()
    {
        return $this->isNew;
    }
            
}


?>
