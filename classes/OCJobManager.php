<?php
/**
 * OCJobManager.php This class manages a bunch of jobs
 *
 */

use Opencast\Models\OCConfig;

class OCJobManager
{
    public static $BASE_PATH = '/opencast';
    public static $CACHE_SUCCESS = 604800;  // 7 days
    public static $CACHE_FAILURE = 1209600; // 14 days

    /**
     * Get the path to the job with a specific id
     *
     * @param $job_id string a unique job id
     *
     * @return string
     */
    public static function job_path($job_id)
    {
        return $GLOBALS['TMP_PATH'] . static::$BASE_PATH . '/' . $job_id;
    }

    /**
     * @param $job_id string a unique job id
     *
     * @return bool true if the job exist
     */
    public static function job_exist($job_id)
    {
        return file_exists(static::job_path($job_id));
    }

    /**
     * Creates a job of a pile of arguments
     *
     * @param $job_id
     * @param $course_id
     * @param $series_id
     * @param $flavor
     * @param $title
     * @param $creator
     * @param $record_date
     * @param $start_hour
     * @param $start_minute
     * @param $contributor
     * @param $subject
     * @param $language
     * @param $description
     * @param $file_size
     * @param $file_name
     * @param $file_type
     */
    public static function create_job(
        $job_id, $course_id, $series_id, $flavor,
        $title, $creator, $record_date, $start, $contributor, $subject, $language, $description,
        $file_size, $file_name, $file_type)
    {
        $location = new OCJobLocation($job_id);
        $location->create();
        $job_data = new OCJsonFile($location->path() . '/job_data.json');
        $opencast_data = new OCJsonFile($location->path() . '/opencast_info.json');
        $job_data['id_list'] = ['job' => $job_id, 'course' => $course_id, 'series' => $series_id];
        $job_data['file'] = ['name' => $file_name, 'size' => $file_size, 'type' => $file_type];

        $job_data['info'] = [
            'title'       => $title,
            'creator'     => $creator,
            'record_date' => $record_date,
            'start'       => $start,
            'contributor' => $contributor,
            'subject'     => $subject,
            'language'    => $language,
            'description' => $description
        ];

        $job_data['creation_timestamp'] = time();
        $opencast_data['media_package'] = 'NOT GENERATED';
        $opencast_data['opencast_job_id'] = 'NOT GENERATED';
        $opencast_data['flavor'] = $flavor;
    }

    /**
     * @param $range string something like 'x-y/z' or '0'
     *
     * @return int
     */
    public static function calculate_chunk_number_from_range($range)
    {
        $config = OCConfig::getConfigForCourse(Context::getId());

        $pattern = '/(\d*)-\d*\/\d*/';
        $result = preg_match($pattern, $range, $matches);
        if ($result) {
            return $matches[1] / $config['upload_chunk_size'];
        }

        return 0;
    }

    /**
     * The path to a chunk-file with a specific number
     *
     * @param $job_id
     * @param $chunk_number
     *
     * @return string the path
     */
    public static function chunk_path($job_id, $chunk_number)
    {
        return static::job_path($job_id) . '/chunk_' . $chunk_number . '.part';
    }

    /**
     * Is the matterhorn service available?
     * @return bool true if it is
     */
    public static function matterhorn_service_available()
    {
        $configuration = OCConfig::getBaseServerConf(1);
        $service_url = parse_url($configuration['service_url']);

        $socket = @fsockopen($service_url['host'], $service_url['port'] ? : 80, $err_number, $err_message, 1);

        if ($socket === false) {
            return false;
        }
        fclose($socket);

        return true;
    }

    /**
     * For the lazy ones or the upload-controller
     * @return OCJob
     */
    public static function from_request()
    {
        $job_id = Request::get('uuid');
        if (!OCJobManager::job_exist($job_id)) {
            OCJobManager::create_job(
                $job_id,
                Request::get('cid'),
                Request::get('series_id'),
                'presenter/source',
                Request::get('title'),
                Request::get('creator'),
                Request::get('recordDate'),
                Request::get('startTime'),
                Request::get('contributor'),
                Request::get('subject'),
                Request::get('language'),
                Request::get('description'),
                Request::get('total_file_size'),
                Request::get('file_name'),
                $_FILES['video']['type']
            );
        }

        return new OCJob($job_id);
    }

    /**
     * Removes old jobs and their file-structure
     */
    public static function cleanup()
    {
        $job_ids = static::existent_jobs();
        foreach ($job_ids as $job_id) {
            $job = new OCJob($job_id);
            $minimum_success_time = time() - static::$CACHE_SUCCESS;
            $minimum_failure_time = time() - static::$CACHE_FAILURE;
            if (($job->both_uploads_succeeded() && $job->created_at_time() < $minimum_success_time) ||
                (!$job->both_uploads_succeeded() && $job->created_at_time() < $minimum_failure_time)
            ) {
                $job->clear_files();
            }
        }
    }

    /**
     * @return array list of existent job ids
     */
    public static function existent_jobs()
    {
        if (!is_dir(self::path())) {
            mkdir(self::path(), 0775, true);

            return [];
        }

        return array_filter(scandir(self::path()), function ($value) {
            return self::is_valid_md5($value);
        });
    }

    private static function is_valid_md5($string_to_test)
    {
        return strlen($string_to_test) == 32 && ctype_xdigit($string_to_test);
    }

    /**
     * Try to reupload old jobs
     */
    public static function try_reupload_old_jobs()
    {
        $job_ids = static::existent_jobs();
        foreach ($job_ids as $job_id) {
            $job = new OCJob($job_id);
            echo "Versuche Upload von '" . $job_id . "'...";
            $job->try_upload_to_opencast();
            echo "Beende Upload von '" . $job_id . "'...";
        }
    }

    public static function save_dir_size()
    {
        $space_in_byte = [
            'total' => disk_total_space(self::path()),
            'free'  => disk_free_space(self::path())
        ];
        $space_in_byte['used'] = $space_in_byte['total'] - $space_in_byte['free'];

        $space_polished = [];
        foreach ($space_in_byte as $key => $value) {
            $space_polished[$key] = static::nice_size_text($value);
        }

        return [
            'bytes'    => $space_in_byte,
            'readable' => $space_polished
        ];
    }

    public static function nice_size_text($size, $precision = 2, $conversion_factor = 1000, $display_threshold = 0.5)
    {
        $possible_sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB'];
        for ($depth = 0; $depth < count($possible_sizes); $depth++) {
            if (($size / $conversion_factor) > $display_threshold) {
                $size /= $conversion_factor;
            } else {
                return round($size, $precision) . ' ' . $possible_sizes[$depth];
            }
        }

        return $size;
    }

    public static function path()
    {
        return $GLOBALS['TMP_PATH'] . static::$BASE_PATH;
    }
}
