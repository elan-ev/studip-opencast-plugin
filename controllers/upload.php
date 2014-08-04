<?php
/*
 * course.php - course controller
 * Copyright (c) 2010  André Klaßen
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once 'app/controllers/authenticated_controller.php';
require_once $this->trails_root.'/classes/OCRestClient/SearchClient.php';
require_once $this->trails_root.'/classes/OCRestClient/SeriesClient.php';
require_once $this->trails_root.'/classes/OCRestClient/IngestClient.php';
require_once $this->trails_root.'/classes/OCRestClient/UploadClient.php';
require_once $this->trails_root.'/classes/OCRestClient/MediaPackageClient.php';
require_once $this->trails_root.'/classes/OCUploadFile.php';
require_once $this->trails_root.'/classes/OCUpload.php';
require_once $this->trails_root.'/models/OCModel.php';
require_once $this->trails_root.'/models/OCSeriesModel.php';

class UploadController extends AuthenticatedController
{
    private $file = null;
    private $error = array();
    private $upload = null;
    private $ingest = null;
    
    public function upload_file_action() 
    {
        $OCUpload = new OCUpload();
        //checks if file is uploaded and returns file object
        if($this->file = $OCUpload->post()) {
            $this->upload = UploadClient::getInstance();
            $this->ingest = IngestClient::getInstance();
            
            if($this->file->isNew()) {
                $this->initNewUpload();
            }
            if($this->file->getMediaPackage() && $this->file->getJobID()) {
                //Step 2.2 Upload all chunks
                $this->uploadChunk();
            } else {
                $this->error[] = _('Fehler beim erstellen der Job ID oder des '
                        .'Media Packages');
            }
            //check if last chunk is handled
            if($this->upload->isLastChunk($this->file->getJobID())) {
                $this->endUpload();
            }
            
        } else { //if($file = $OCUpload->post())
               $this->error[] = _('Fehler beim hochladen der Datei');
        }
        $this->render_nothing();
    }
    private function endUpload()
    {
        //Step 2.2 Wait for upload job finalizing
        while(!$this->upload->isComplete($this->file->getJobID())) {
            usleep(500);
        }
        //Step 2.3  Add track -- for every file successfully uploaded to
        //          get the updated media package
        if(!$this->addTrack()) {
            $this->error[] = _('Fehler beim hinzufügen des Tracks');
            return false;
        } 
        //Step 3. Add catalogs (e.g. series.xml, episode.xml)
        if(!$this->addSeriesDC()) {
            $this->error[] = _('Fehler beim hinzufügen der Series');
            return false;
        }
        if(!$this->addEpisodeDC()) {
            $this->error[] = _('Fehler beim hinzufügen der Episode');
            return false;
        }
        if($content = $this->ingest->ingest($this->file->getMediaPackage()))
        {
            $this->file->clearSession();
            echo 'Ingest Started: '.htmlentities($content);
        } else echo 'upload failed';
        
    }
   
    private function addTrack()
    {
        $mediaPClient = MediaPackageClient::getInstance();

        $trackURI = $this->upload->getTrackURI($this->file->getJobID());
        $newMPackage = $mediaPClient->addTrack($this->file->getMediaPackage(),
                $trackURI,
                $this->file->getFlavor());

        if(!$newMPackage) {
            return false;
        } else {
            $this->file->setMediaPackage($newMPackage);
            return true;
        }
        
    }
    private function addEpisodeDC()
    {
        $episodeDC = $this->file->getEpisodeDC();
        $newMediaPackage = '';
        if($newMediaPackage = $this->ingest->addDCCatalog(
                $this->file->getMediaPackage(), 
                $episodeDC, 
                'dublincore/episode'))
        {
            $this->file->setMediaPackage($newMediaPackage);
            return true;
        } else {
            $this->error[] = 'Fehler beim hinzufügen einer Episode, '.$episodeDC;
            return false;
        }
    }
    private function addSeriesDC() {
        $seriesDCs = OCSeriesModel::getSeriesDCs($_SESSION['SessionSeminar']);
        foreach($seriesDCs as $dc) 
        {
            $newMediaPackage = '';
            if($newMediaPackage = $this->ingest->addDCCatalog(
                    $this->file->getMediaPackage(), 
                    $dc, 
                    'dublincore/series'))
            {
                $this->file->setMediaPackage($newMediaPackage);
            } else {
                $this->error[] = 'Fehler beim hinzufügen einer Series, '.$dc;
                return false;
            }
        }
        return true;
    }
    private function initNewUpload()
    {
        //Step 1. Create new mediapackage
        if($res = $this->ingest->createMediaPackage()) {
            $this->file->setMediaPackage($res);
            //Step 2.1 Generate job ID -- for every new track upload job
            if($jobID = $this->upload->newJob($this->file->getName(), 
                                        $this->file->getSize(), 
                                        OC_UPLOAD_CHUNK_SIZE, 
                                        $this->file->getFlavor(), 
                                        $this->file->getmediaPackage())) {
                $this->file->setJobID($jobID);
            } else {
                $this->error[] = _('Fehler beim anlegen der Job ID');
            }
        } else {
            $this->error[] = _('Fehler beim anlegen des Media Packages');
        }
        //TODO: sicherheit Request
        foreach($_POST as $key => $val) {
            $episodeData[$key] = Request::get($key);
        }
        
        $this->file->setEpisodeData($episodeData);
    }
    private function uploadChunk()
    {
        while($this->upload->isInProgress($this->file->getJobID()))
        {
            usleep(500);
        }
      
        $res = $this->upload->uploadChunk($this->file->getJobID(),
                $this->file->getChunk(),
                '@'.$this->file->getChunkPath());
        switch($res[1]) {
            case 200: 
                $this->file->setChunkStatus('mh');
                break;
            case 400:
                $this->file
                ->setChunkError('400: Bad Request, the request was malformed');
                break;
            case 404:
                $this->file
                ->setChunkError('404: Not Found, the job was not found.');
                break;
        }
        if($this->file->getChunkStatus() == 'error') {
            $this->error[] = 'Fehler beim upload zu Matterhorn: '
                    . $this->file->getChunkError();
            return false;
        } else return true;
    }
}