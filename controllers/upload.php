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

require_once 'lib/log_events.inc.php';

require_once $this->trails_root.'/classes/OCRestClient/SearchClient.php';
require_once $this->trails_root.'/classes/OCRestClient/SeriesClient.php';
require_once $this->trails_root.'/classes/OCRestClient/IngestClient.php';
require_once $this->trails_root.'/classes/OCRestClient/UploadClient.php';
require_once $this->trails_root.'/classes/OCRestClient/ArchiveClient.php';
require_once $this->trails_root.'/classes/OCUploadFile.php';
require_once $this->trails_root.'/classes/OCUpload.php';
require_once $this->trails_root.'/models/OCModel.php';
require_once $this->trails_root.'/models/OCSeriesModel.php';
require_once $this->trails_root.'/models/OCCourseModel.class.php';

class UploadController extends OpencastController
{
    /** @var OCUploadFile */
    private $file = null;
    private $error = array();
    /** @var UploadClient */
    private $upload = null;
    /** @var IngestClient */
    private $ingest = null;

    /**
     * Constructs the controller and provide translations methods.
     *
     * @param object $dispatcher
     * @see https://stackoverflow.com/a/12583603/982902 if you need to overwrite
     *      the constructor of the controller
     */
    public function __construct($dispatcher)
    {
        parent::__construct($dispatcher);

        $this->plugin = $dispatcher->current_plugin;

        // Localization
        $this->_ = function ($string) use ($dispatcher) {
            return call_user_func_array(
                [$dispatcher->current_plugin, '_'],
                func_get_args()
            );
        };

        $this->_n = function ($string0, $tring1, $n) use ($dispatcher) {
            return call_user_func_array(
                [$dispatcher->current_plugin, '_n'],
                func_get_args()
            );
        };
    }

    /**
     * Intercepts all non-resolvable method calls in order to correctly handle
     * calls to _ and _n.
     *
     * @param string $method
     * @param array  $arguments
     * @return mixed
     * @throws RuntimeException when method is not found
     */
    public function __call($method, $arguments)
    {
        $variables = get_object_vars($this);
        if (isset($variables[$method]) && is_callable($variables[$method])) {
            return call_user_func_array($variables[$method], $arguments);
        }
        throw new RuntimeException("Method {$method} does not exist");
    }

    public function upload_file_action()
    {
        $OCUpload = new OCUpload();

        tglog('Upload another chunk...');

        //checks if file is uploaded and returns file object
        if($this->file = $OCUpload->post()) {
            tglog('##1 - check if some filedata has been posted passed');
            $this->upload = UploadClient::getInstance();
            $this->ingest = IngestClient::getInstance();

            $this->endUpload();

            /*
            if($this->file->isNew()) {
                tglog('##2 - we have a new upload, initialize it');
                $this->initNewUpload();
            }
            */

            /*
            if($this->file->getMediaPackage() && $this->file->getJobID()) {
                tglog('##3 - upload the chunk');
                //Step 2.2 Upload all chunks

                $x = $this->uploadChunk();
                //file_put_contents("/tmp/oc_log.txt", 'Chunk hochgeladen um: '  . date('d.m.Y H:i:s',time()) .' Uhr: ' . $x[1] .'\n');
            } else {
                tglog('##4 - error in mediapackage or job');
                $this->error[] = $this->_('Fehler beim erstellen der Job ID oder des '
                        .'Media Packages');
            }
            //check if last chunk is handled
            if($this->upload->isLastChunk($this->file->getJobID())) {
                tglog('##5 - last chunk uploaded, end the upload');
                //file_put_contents("/tmp/oc_log.txt", 'Job finalisieren um: '  . date('d.m.Y H:i:s',time()) .' Uhr \n');
                $this->endUpload();
                //file_put_contents("/tmp/oc_log.txt", 'Job fertig um: '  . date('d.m.Y H:i:s',time()) .' Uhr \n');
            }
            */

        } else { //if($file = $OCUpload->post())
            tglog('##6 - error while receiving filedata');
            $this->error[] = $this->_('Fehler beim hochladen der Datei');
        }

        if (!empty($this->error)) {
            $this->flash['messages'] = array('error' => implode('<br>', $this->error));
        }

        $this->redirect('course/index');
    }


    private function endUpload()
    {
        //Step 2.2 Wait for upload job finalizing
        /*
        while(!$this->upload->isComplete($this->file->getJobID())) {
            usleep(500);
        }
        */

        //Step 2.3  Add track -- for every file successfully uploaded to
        //          get the updated media package
        //
        tglog('end upload');

        if(!$this->addTrack()) {
            $this->error[] = $this->_('Fehler beim hinzufügen des Tracks');
            tglog('Fehler beim hinzufügen des Tracks');
            return false;
        }

        //Step 3. Add catalogs (e.g. series.xml, episode.xml)
        if(!$this->addSeriesDC()) {
            $this->error[] = $this->_('Fehler beim hinzufügen der Series');
            tglog('Fehler beim hinzufügen der Series');
            return false;
        }

        if(!$this->addEpisodeDC()) {
            $this->error[] = $this->_('Fehler beim hinzufügen der Episode');
            tglog('Fehler beim hinzufügen der Episode');
            return false;
        }
        // comment indicates how specific workflows can be chosen

        $course_id =  $_SESSION['SessionSeminar'];
        $occourse = new OCCourseModel($course_id);
        $uploadwf = $occourse->getWorkflow('upload');

        if ($uploadwf) {
            $workflow = $uploadwf['workflow_id'];

        } else {
            $workflow = get_config('OPENCAST_WORKFLOW_ID');
        }

        /** @var IngestClient $ingestClient */
        $ingestClient = IngestClient::getInstance();

        tglog($this->file->getMediaPackage());

        if ($content = $ingestClient->ingest($this->file->getMediaPackage(), $workflow))
        {

            $simplexml = simplexml_load_string($content);
            $json = json_encode($simplexml);
            $x = json_decode($json, true);
            $result = $x['@attributes'];

            OCModel::setWorkflowIDforCourse($result['id'], $course_id, $GLOBALS['auth']->auth['uid'], time());

            $this->file->clearSession();
            log_event('OC_UPLOAD_MEDIA', $result['id'], $_SESSION['SessionSeminar']);
            //echo 'Ingest Started: '.htmlentities($content);
        } else echo 'upload failed';

    }

    private function addTrack()
    {
        /** @var IngestClient $ingestClient */
        $ingestClient = IngestClient::getInstance();

        $trackURI = $this->upload->getTrackURI($this->file->getJobID());

        $newMPackage = $ingestClient->addTrack($this->file->getMediaPackage(),
                $trackURI,
                $this->file->getFlavor());

        if (!$newMPackage) {
            die('could not add track!');
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

        if ($newMediaPackage = $this->ingest->addDCCatalog(
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

        if (is_array($seriesDCs)) {
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
        }

        return true;
    }

    /**
     * [initNewUpload description]
     * @return [type] [description]
     */
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
                                        $this->file->getMediaPackage())) {
                $this->file->setJobID($jobID);
            } else {
                $this->error[] = $this->_('Fehler beim anlegen der Job ID');
            }
        } else {
            $this->error[] = $this->_('Fehler beim anlegen des Media Packages');
        }
        //TODO: sicherheit Request
        foreach($_POST as $key => $val) {
            $value  = Request::get($key);
            $value = mb_convert_encoding($value,"ISO-8859-1",'auto');
            $episodeData[$key] = $value;
        }

        $this->file->setEpisodeData($episodeData);
    }

    /**
     * [uploadChunk description]
     * @return [type] [description]
     */
    private function uploadChunk()
    {
        while($this->upload->isInProgress($this->file->getJobID()))
        {
            usleep(500);
        }

        // tglog('Chunk: ' . print_r($this->file, 1));
        $res = $this->upload->uploadChunk($this->file->getJobID(),
                $this->file->getChunk(),
                $this->file->getChunkPath());
        switch($res[0]) {
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
            tglog('Fehler: ' . $this->file->getChunkError());
            return false;
        } else return $res; //true;
    }
}
