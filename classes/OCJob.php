<?php
/**
 * @author          Jan-Frederik Leissner <jleissner@uos.de>
 * @copyright   (c) Authors
 * @version         1.0 (10:05)
 */

class OCJob
{
    private $id;                // local id
    private $data;              // readable job info
    private $local_chunk_info;
    private $upload_chunk_info;
    private $opencast_info;

    private $ingest_client;
    private $upload_client;

    public function __construct($id)
    {
        $this->id = $id;
        $this->load_job_data();
        $this->ingest_client = new IngestClient();
        $this->upload_client = new UploadClient();
        $this->opencast_init();
    }

    private function load_job_data()
    {
        $data_file = new OCJsonFile(OCJobManager::job_path($this->id) . '/job_data.json');
        $this->data = $data_file->content;
        $this->local_chunk_info = new OCJsonFile(OCJobManager::job_path($this->id) . '/local_chunk_info.json');
        $this->upload_chunk_info = new OCJsonFile(OCJobManager::job_path($this->id) . '/upload_chunk_info.json');
        $this->opencast_info = new OCJsonFile(OCJobManager::job_path($this->id) . '/opencast_info.json');
    }

    public function data()
    {
        return $this->data;
    }

    public function created_at_time()
    {
        return $this->data['creation_timestamp'];
    }

    public function number_of_chunks()
    {
        return ceil($this->data['file']['size'] / OC_UPLOAD_CHUNK_SIZE);
    }

    public function upload_local($tmp_path, $chunk_number)
    {
        if (move_uploaded_file($tmp_path, OCJobManager::chunk_path($this->id, $chunk_number))) {
            $this->local_chunk_info['chunk_' . $chunk_number] = array(
                'number'            => $chunk_number,
                'local_upload_time' => time()
            );
            $this->upload_chunk_info['chunk_' . $chunk_number] = array(
                'number'         => $chunk_number,
                'upload_tries'   => 0,
                'upload_success' => FALSE,
                'removed'        => FALSE
            );
        }
    }

    public function missing_local_chunks()
    {
        $chunk_amount = $this->number_of_chunks();
        $missing = array();
        for ($index = 0; $index < $chunk_amount; $index++) {
            if (!isset($this->local_chunk_info['chunk_' . $index])) {
                $missing[] = 'chunk_' . $index;
            }
        }

        return $missing;
    }

    public function missing_upload_chunks()
    {
        $chunk_amount = $this->number_of_chunks();
        $missing = array();
        for ($index = 0; $index < $chunk_amount; $index++) {
            if (
                isset($this->upload_chunk_info['chunk_' . $index]) &&
                !$this->upload_chunk_info['chunk_' . $index]['upload_success']) {
                $missing[] = 'chunk_' . $index;
            }
        }

        return $missing;
    }

    private function opencast_unloaded($what)
    {
        return in_array($this->opencast_info[$what], array('NOT GENERATED', 'GENERATION ERROR'));
    }

    public function load_media_package()
    {
        if ($this->opencast_unloaded('media_package')) {
            $media_package = $this->ingest_client->createMediaPackage();
            if ($media_package === FALSE) {
                $this->opencast_info['media_package'] = 'GENERATION ERROR';
            } else {
                $this->opencast_info['media_package'] = $media_package;
            }
        }
    }

    public function load_opencast_job_id()
    {
        if (!$this->opencast_unloaded('media_package') && $this->opencast_unloaded('opencast_job_id')) {
            $opencast_job_id = $this->upload_client->newJob(
                $this->data['file']['name'],
                $this->data['file']['size'],
                OC_UPLOAD_CHUNK_SIZE,
                $this->opencast_info['flavor'],
                $this->opencast_info['media_package']
            );
            if ($opencast_job_id === FALSE) {
                $this->opencast_info['opencast_job_id'] = 'GENERATION ERROR';
            } else {
                $this->opencast_info['opencast_job_id'] = $opencast_job_id;
            }
        }
    }

    private function opencast_init()
    {
        if (OCJobManager::matterhorn_service_available()) {
            $this->load_media_package();
            $this->load_opencast_job_id();
        }
    }

    public function upload_opencast($chunk_name)
    {
        $this->upload_chunk_info->load();
        $this->upload_chunk_info->content[$chunk_name]['upload_tries'] += 1;
        $this->upload_chunk_info->save();
        if(OCJobManager::matterhorn_service_available()){
            $this->wait_for_previous_upload(5, 500);
            $chunk_number = $this->upload_chunk_info[$chunk_name]['number'];
            $result = $this->upload_client->uploadChunk(
                $this->opencast_info['opencast_job_id'],
                $chunk_number,
                array(
                    'name'     => OCJobManager::chunk_path($this->id, $chunk_number),
                    'mime'     => $this->data['file']['type'],
                    'postname' => $this->data['file']['name']
                )
            );
            if ($result !== NULL) {
                $this->upload_chunk_info->load();
                $this->upload_chunk_info[$chunk_name]['upload_success'] = TRUE;
                $this->upload_chunk_info->save();
                $this->upload_chunk_info->load();
                $this->upload_chunk_info[$chunk_name]['removed'] = unlink(OCJobManager::chunk_path($this->id, $chunk_number));
                $this->upload_chunk_info->save();
            }
        }
    }

    private function wait_for_previous_upload($max_time_s, $sleep_time_ms)
    {
        $sleep_time = 0;
        if (OCJobManager::matterhorn_service_available()) {
            while ($sleep_time < ($max_time_s * 1000000) && $this->upload_client->isInProgress($this->opencast_info['opencast_job_id'])) {
                $sleep_time += $sleep_time_ms;
                usleep($sleep_time_ms);
            }
        }
    }

    private function wait_for_completeness($max_time_s, $sleep_time_ms)
    {
        $sleep_time = 0;
        if (OCJobManager::matterhorn_service_available()) {
            while ($sleep_time < ($max_time_s * 1000000) && !$this->upload_client->isComplete($this->opencast_info['opencast_job_id'])) {
                $sleep_time += $sleep_time_ms;
                usleep($sleep_time_ms);
            }
        }
    }

    public function finish_upload()
    {
        if (OCJobManager::matterhorn_service_available()) {
            if ($this->both_uploads_succeeded()) {
                $this->wait_for_completeness(5, 500);
                $result = array(
                    'track'   => $this->add_track_to_media_package(),
                    'series'  => $this->add_series_dcs_to_media_package(),
                    'episode' => $this->add_episode_dc_to_media_package(),
                );
                $this->opencast_info['upload'] = $result;
            }
        }
    }

    private function add_track_to_media_package()
    {
        $track_uri = $this->upload_client->getTrackURI($this->opencast_info['opencast_job_id']);
        $media_package = $this->ingest_client->addTrack(
            $this->opencast_info['media_package'],
            $track_uri,
            $this->opencast_info['flavor']
        );
        $result = 'SUCCESS';
        if ($media_package === FALSE) {
            $result = 'ERROR';
        } else {
            $this->opencast_info['media_package'] = $media_package;
        }

        return $result;
    }

    private function add_series_dcs_to_media_package()
    {
        $series_dcs = OCSeriesModel::getSeriesDCs($this->data['id_list']['course']);
        $result = array();
        if (is_array($series_dcs)) {
            foreach ($series_dcs as $dc) {
                $media_package = $this->ingest_client->addDCCatalog(
                    $this->opencast_info['media_package'],
                    $dc,
                    'dublincore/series'
                );
                if ($media_package === FALSE) {
                    $result[] = 'ERROR WITH: ' . $dc;
                } else {
                    $this->opencast_info['media_package'] = $media_package;
                    $result[] = 'SUCCESS';
                }
            }
        }

        return $result;
    }

    private function add_episode_dc_to_media_package()
    {
        $media_package = $this->ingest_client->addDCCatalog(
            $this->opencast_info['media_package'],
            $this->get_episode_dc(),
            'dublincore/episode'
        );
        $result = 'SUCCESS';
        if ($media_package === FALSE) {
            $result = 'ERROR';
        } else {
            $this->opencast_info['media_package'] = $media_package;
        }

        return $result;
    }

    private function get_episode_dc()
    {
        $info = $this->data['info'];
        $ids = $this->data['id_list'];
        $creatition_time = new DateTime($info['record_date'] . ' ' . $info['start']['h'] . ':' . $info['start']['h']);
        $dc_values = array();
        $dc_values['title'] = utf8_encode($info['title']);
        $dc_values['creator'] = utf8_encode($info['creator']);
        $dc_values['contributor'] = utf8_encode($info['contributor']);
        $dc_values['subject'] = utf8_encode($info['subject']);
        $dc_values['language'] = utf8_encode($info['language']);
        $dc_values['description'] = utf8_encode($info['description']);
        $dc_values['series_id'] = utf8_encode($ids['series']);
        $dublincore = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
                            <dublincore xmlns="http://www.opencastproject.org/xsd/1.0/dublincore/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                                <dcterms:creator><![CDATA[' . $dc_values['creator'] . ']]></dcterms:creator>
                                <dcterms:contributor><![CDATA[' . $dc_values['contributor'] . ']]></dcterms:contributor>
                                <dcterms:subject><![CDATA[' . $dc_values['subject'] . ']]></dcterms:subject>
                                <dcterms:created xsi:type="dcterms:W3CDTF">' . OCModel::getDCTime($creatition_time->getTimestamp()) . '</dcterms:created>
                                <dcterms:description><![CDATA[' . $dc_values['description'] . ']]></dcterms:description>
                                <dcterms:language><![CDATA[' . $dc_values['language'] . ']]></dcterms:language>
                                <dcterms:title><![CDATA[' . $dc_values['title'] . ']]></dcterms:title>
                                <dcterms:isPartOf><![CDATA[' . $dc_values['series_id'] . ']]></dcterms:isPartOf>
                            </dublincore>';

        return $dublincore;
    }

    public function trigger_ingest()
    {
        if ($this->both_uploads_succeeded()) {
            $occourse = new OCCourseModel($this->data['id_list']['course']);
            $uploadwf = $occourse->getWorkflow('upload');
            if ($uploadwf) {
                $workflow = $uploadwf['workflow_id'];
            } else {
                $workflow = get_config('OPENCAST_WORKFLOW_ID');
            }
            $content = $this->ingest_client->ingest($this->opencast_info['media_package'], $workflow);
            if ($content === FALSE) {
                $this->opencast_info['ingest'] = 'ERROR';
            } else {
                $simplexml = simplexml_load_string($content);
                $json = json_encode($simplexml);
                $x = json_decode($json, TRUE);
                $result = $x['@attributes'];
                OCModel::setWorkflowIDforCourse($result['id'], $this->data['id_list']['course'], $GLOBALS['auth']->auth['uid'], time());
                $this->opencast_info['ingest'] = $result;
            }
        }
    }

    public function both_uploads_succeeded()
    {
        return count($this->missing_upload_chunks()) == 0 && count($this->missing_local_chunks()) == 0;
    }

    public function clear_files()
    {
        $path = OCJobManager::job_path($this->id);
        $files = array_diff(scandir($path), array('..', '.'));
        foreach ($files as $file) {
            unlink($path . '/' . $file);
        }
        rmdir($path);
    }

    public function try_upload_to_opencast()
    {
        //Gather all missing chunks for the current job
        $missing = $this->missing_upload_chunks();
        foreach ($missing as $chunk_name){
            $this->upload_opencast($chunk_name);
        }

        //If possible, finish current upload and trigger ingest
        $this->finish_upload();
        $this->trigger_ingest();
    }

    public function upload_local_from_controller()
    {
        $source = $_FILES['video']['tmp_name'];
        $chunk_number = OCJobManager::calculate_chunk_number_from_range((
        isset($_SERVER['HTTP_CONTENT_RANGE'])?$_SERVER['HTTP_CONTENT_RANGE']:0
        ));
        $this->upload_local(
            $source,
            $chunk_number
        );
    }
}