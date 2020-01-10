<?php
/**
 * This class handle all job related activities
 *
 */

use Opencast\Models\OCConfig;

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

    /**
     * @return array retrieve the basic job-info
     */
    public function data()
    {
        return $this->data;
    }

    public function complete_data()
    {
        return [
            'job_data'          => new OCJsonFile(OCJobManager::job_path($this->id) . '/job_data.json'),
            'local_chunk_info'  => new OCJsonFile(OCJobManager::job_path($this->id) . '/local_chunk_info.json'),
            'upload_chunk_info' => new OCJsonFile(OCJobManager::job_path($this->id) . '/upload_chunk_info.json'),
            'opencast_info'     => new OCJsonFile(OCJobManager::job_path($this->id) . '/opencast_info.json')
        ];
    }

    /**
     * @return int retrieve the time when the job was created
     */
    public function created_at_time()
    {
        return $this->data['creation_timestamp'];
    }

    /**
     * @return int get the absolute number of chunks for this job
     */
    public function number_of_chunks()
    {
        $config = OCConfig::getConfigForCourse(Context::getId());
        return ceil($this->data['file']['size'] / $config['upload_chunk_size']);
    }

    /**
     * Upload a chunk locally
     *
     * @param $tmp_path     string the path to the source file
     * @param $chunk_number int the number for this chunk
     */
    public function upload_local($tmp_path, $chunk_number)
    {
        if (move_uploaded_file($tmp_path, OCJobManager::chunk_path($this->id, $chunk_number))) {
            chmod(OCJobManager::chunk_path($this->id, $chunk_number), 0775);

            $this->local_chunk_info['chunk_' . $chunk_number] = [
                'number'            => $chunk_number,
                'local_upload_time' => time()
            ];
            $this->upload_chunk_info['chunk_' . $chunk_number] = [
                'number'         => $chunk_number,
                'upload_tries'   => 0,
                'upload_success' => false,
                'removed'        => false
            ];
        }
    }

    /**
     * @return array list of missing local chunk numbers
     */
    public function missing_local_chunks()
    {
        $chunk_amount = $this->number_of_chunks();
        $missing = [];
        for ($index = 0; $index < $chunk_amount; $index++) {
            if (!isset($this->local_chunk_info['chunk_' . $index])) {
                $missing[] = 'chunk_' . $index;
            }
        }

        return $missing;
    }

    /**
     * @return array list of chunk numbers not yet uploaded to opencast
     */
    public function missing_upload_chunks()
    {
        $chunk_amount = $this->number_of_chunks();
        $missing = [];
        for ($index = 0; $index < $chunk_amount; $index++) {
            if (
                isset($this->upload_chunk_info['chunk_' . $index]) &&
                !$this->upload_chunk_info['chunk_' . $index]['upload_success']) {
                $missing[] = 'chunk_' . $index;
            }
        }

        return $missing;
    }

    /**
     * Check if something is unloaded
     *
     * @param $what
     *
     * @return bool
     */
    private function opencast_unloaded($what)
    {
        return in_array($this->opencast_info[$what], ['NOT GENERATED', 'GENERATION ERROR']);
    }

    /**
     * Load the media package or create if there is none
     */
    public function load_media_package()
    {
        if ($this->opencast_unloaded('media_package')) {
            $media_package = $this->ingest_client->createMediaPackage();
            if ($media_package === false) {
                $this->opencast_info['media_package'] = 'GENERATION ERROR';
            } else {
                $this->opencast_info['media_package'] = $media_package;
            }
        }
    }

    /**
     * Load the opencast job id or create if there is none
     */
    public function load_opencast_job_id()
    {
        $config = OCConfig::getConfigForCourse(Context::getId());

        if (!$this->opencast_unloaded('media_package') && $this->opencast_unloaded('opencast_job_id')) {
            $opencast_job_id = $this->upload_client->newJob(
                $this->data['file']['name'],
                $this->data['file']['size'],
                $config['upload_chunk_size'],
                $this->opencast_info['flavor'],
                $this->opencast_info['media_package']
            );
            if ($opencast_job_id === false) {
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

    /**
     * Uploads a chunk to opencast
     *
     * @param $chunk_name (like 'chunk_1')
     */
    public function upload_opencast($chunk_name)
    {
        $this->upload_chunk_info->load();
        $this->upload_chunk_info->content[$chunk_name]['upload_tries'] += 1;
        $this->upload_chunk_info->save();

        if (OCJobManager::matterhorn_service_available()) {
            $this->wait_for_previous_upload(5, 500);
            $chunk_number = $this->upload_chunk_info[$chunk_name]['number'];

            $result = $this->upload_client->uploadChunk(
                $this->opencast_info['opencast_job_id'],
                $chunk_number,
                [
                    'name'     => OCJobManager::chunk_path($this->id, $chunk_number),
                    'mime'     => $this->data['file']['type'],
                    'postname' => $this->data['file']['name']
                ]
            );

            if ($result !== null) {
                $this->upload_chunk_info->load();
                $this->upload_chunk_info[$chunk_name]['upload_success'] = true;
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

    /**
     * Finising touch after every chunk was uploaded
     */
    public function finish_upload()
    {
        if (OCJobManager::matterhorn_service_available()) {
            if ($this->both_uploads_succeeded()) {
                $this->wait_for_completeness(5, 500);
                $result = [
                    'track'   => $this->add_track_to_media_package(),
                    'series'  => $this->add_series_dcs_to_media_package(),
                    'episode' => $this->add_episode_dc_to_media_package(),
                ];
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
        if ($media_package === false) {
            $result = 'ERROR';
        } else {
            $this->opencast_info['media_package'] = $media_package;
        }

        return $result;
    }

    private function add_series_dcs_to_media_package()
    {
        $series_dcs = OCSeriesModel::getSeriesDCs($this->data['id_list']['course']);
        $result = [];
        if (is_array($series_dcs)) {
            foreach ($series_dcs as $dc) {
                $media_package = $this->ingest_client->addDCCatalog(
                    $this->opencast_info['media_package'],
                    $dc,
                    'dublincore/series'
                );
                if ($media_package === false) {
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
        if ($media_package === false) {
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
        $creation_time = new DateTime($info['record_date'] . ' ' . $info['start']);
        $dc_values = [];

        $dc_values['title'] = $info['title'];
        $dc_values['creator'] = $info['creator'];
        $dc_values['contributor'] = $info['contributor'];
        $dc_values['subject'] = $info['subject'];
        $dc_values['language'] = $info['language'];
        $dc_values['description'] = $info['description'];
        $dc_values['series_id'] = $ids['series'];
        $dublincore = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
                            <dublincore xmlns="http://www.opencastproject.org/xsd/1.0/dublincore/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                                <dcterms:creator><![CDATA[' . $dc_values['creator'] . ']]></dcterms:creator>
                                <dcterms:contributor><![CDATA[' . $dc_values['contributor'] . ']]></dcterms:contributor>
                                <dcterms:subject><![CDATA[' . $dc_values['subject'] . ']]></dcterms:subject>
                                <dcterms:created xsi:type="dcterms:W3CDTF">' . OCModel::getDCTime($creation_time->getTimestamp()) . '</dcterms:created>
                                <dcterms:description><![CDATA[' . $dc_values['description'] . ']]></dcterms:description>
                                <dcterms:language><![CDATA[' . $dc_values['language'] . ']]></dcterms:language>
                                <dcterms:title><![CDATA[' . $dc_values['title'] . ']]></dcterms:title>
                                <dcterms:isPartOf><![CDATA[' . $dc_values['series_id'] . ']]></dcterms:isPartOf>
                            </dublincore>';

        return $dublincore;
    }

    /**
     * Triggers the ingest if everything was uploaded correctly ('finish_upload()' should run first!)
     */
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
            if ($content === false) {
                $this->opencast_info['ingest'] = 'ERROR';
            } else {
                $simplexml = simplexml_load_string($content);
                $json = json_encode($simplexml);
                $x = json_decode($json, true);
                $result = $x['@attributes'];
                OCModel::setWorkflowIDforCourse($result['id'], $this->data['id_list']['course'], $GLOBALS['auth']->auth['uid'], time());
                $this->opencast_info['ingest'] = $result;
            }
        }
    }

    /**
     * @return bool true if there are no missing chunks, locally and remotely
     */
    public function both_uploads_succeeded()
    {
        return count($this->missing_upload_chunks()) == 0 && count($this->missing_local_chunks()) == 0;
    }

    /**
     * Clear all the files and directories belonging to this job
     */
    public function clear_files()
    {
        $path = OCJobManager::job_path($this->id);
        $files = array_diff(scandir($path), ['..', '.']);
        foreach ($files as $file) {
            unlink($path . '/' . $file);
        }
        rmdir($path);
    }

    /**
     * Trys the upload of missing chunks to opencast
     */
    public function try_upload_to_opencast()
    {
        //Gather all missing chunks for the current job
        $missing = $this->missing_upload_chunks();
        foreach ($missing as $chunk_name) {
            $this->upload_opencast($chunk_name);
        }

        //If possible, finish current upload and trigger ingest
        $this->finish_upload();
        $this->trigger_ingest();
    }

    /**
     * Just usable from the 'upload.php' controller
     */
    public function upload_local_from_controller()
    {
        $source = $_FILES['video']['tmp_name'];
        $chunk_number = OCJobManager::calculate_chunk_number_from_range((
        isset($_SERVER['HTTP_CONTENT_RANGE']) ? $_SERVER['HTTP_CONTENT_RANGE'] : 0
        ));

        $this->upload_local(
            $source,
            $chunk_number
        );
    }
}
