<?php

namespace Opencast\Models;

use Opencast\Models\PlaylistSeminars;
use Opencast\Models\Video;

class ScheduledRecordings extends \SimpleORMap
{
    protected static function configure($config = array())
    {
        $config['db_table'] = 'oc_scheduled_recordings';

        parent::configure($config);
    }

    /**
     * Adds or updates schedule recording
     *
     * @param string $seminar_id id of course
     * @param string $series_id id of serie
     * @param string $date_id id of termin
     * @param string $resource_id id of resource
     * @param int $start start timestamp
     * @param int $end end timestamp
     * @param string $capture_agent capture agent name
     * @param string $event_id id of oc event
     * @param string $status the status of the record
     * @param string $workflow_id workflow id
     * @param bool $is_livestream livestream indicator
     *
     * @return bool
     * @throws Exception
     */
    public static function setScheduleRecording(
        $seminar_id,
        $series_id,
        $user_id,
        $date_id,
        $resource_id,
        $start,
        $end,
        $capture_agent,
        $event_id,
        $status = 'scheduled',
        $workflow_id = 'full',
        $is_livestream = false)
    {
        if (!empty($seminar_id) && !empty($resource_id) && !empty($date_id)) {
            if (!$scheduled_recording = self::getScheduleRecording($seminar_id, $resource_id, $date_id)) {
                $scheduled_recording = new self();
            }

            $coursedate_start = $start;
            $coursedate_end   = $end;

            $scheduled_recording->setData(
                compact(
                    'seminar_id',
                    'series_id',
                    'user_id',
                    'date_id',
                    'resource_id',
                    'start',
                    'end',
                    'coursedate_start',
                    'coursedate_end',
                    'capture_agent',
                    'event_id',
                    'status',
                    'workflow_id',
                    'is_livestream'
                )
            );
            return $scheduled_recording->store();
        } else {
            throw new \Exception(_('Der geplante Termin wurde nicht korrekt angegeben.'));
        }
    }

    /**
     * Gets the schedule recording record
     *
     * @param string $seminar_id id of course
     * @param string $resource_id id of resource
     * @param string $date_id id of termin
     * @param string $status the status of the record
     *
     * @return object|bool
     */
    public static function getScheduleRecording($seminar_id, $resource_id, $date_id, $status = '')
    {
        $where_array = ["seminar_id = ?", "date_id = ?", "resource_id = ?"];
        $params = [$seminar_id, $date_id ,  $resource_id];
        if (!empty($status)) {
            $where_array[] = "status = ?";
            $params[] = $status;
        }
        return self::findOneBySQL(implode(' AND ', $where_array), $params);
    }

    /**
     * Gets the list of schedule recording based on the parameters passed
     * It will by default get the records in the future.
     *
     * @param string $resource_id id of resource
     * @param string $seminar_id id of course
     * @param string $status the status of the record
     * @param bool|null $is_livestream the livestream flag of the record
     * @param bool $include_old_records If true, includes all records regardless of their date;
     *                                  if false, only future (upcoming) records are returned.
     *
     * @return object|bool
     */
    public static function getScheduleRecordingList(
        $resource_id = null,
        $seminar_id = null,
        $status = '',
        $is_livestream = null)
    {
        $where_array = [];
        $params = [];
        if (!empty($resource_id)) {
            $where_array[] = "resource_id = ?";
            $params[] = $resource_id;
        }
        if (!empty($seminar_id)) {
            $where_array[] = "seminar_id = ?";
            $params[] = $seminar_id;
        }
        if (!empty($status)) {
            $where_array[] = "status = ?";
            $params[] = $status;
        }
        if (!is_null($is_livestream)) {
            $is_livestream = (bool) $is_livestream ? 1 : 0;
            $where_array[] = "is_livestream = ?";
            $params[] = $is_livestream;
        }
        
        // Always retrieve only future records
        $where_array[] = "start >= ?";
        $params[] = time();

        if (!empty($where_array)) {
            return self::findBySQL(implode(' AND ', $where_array), $params);
        }
        return self::findBySQL(1);
    }

    /**
     * Removes a scheduled recording for a given date and resource within a course
     *
     * @param string $event_id id of oc event
     * @param string $resource_id id of resource
     * @param string $date_id id of termin
     *
     * @return boolean
     */

    public static function unscheduleRecording($event_id, $resource_id = null, $date_id = null)
    {
        $params = [$event_id];
        $where_array = ['event_id = ?'];

        if (!empty($resource_id)) {
            $where_array[] = 'resource_id = ?';
            $params[] = $resource_id;
        }
        if (!empty($date_id)) {
            $where_array[] = 'date_id = ?';
            $params[] = $date_id;
        }

        return self::deleteBySql(implode(' AND ', $where_array), $params);
    }

    /**
     * Get scheduled recording object with scheduled as its status
     *
     * @param string $event_id id of oc event
     * @param string $resource_id id of resource
     * @param string $date_id id of termin
     *
     * @return object|null
     */
    public static function checkScheduled($course_id, $resource_id, $date_id)
    {
        return self::getScheduleRecording($course_id, $resource_id, $date_id, 'scheduled');
    }

    /**
     * Get related video object created when the scheduled recordingd is livestream.
     *
     * @return Opencast\Models\Video || null
     */
    public function getVideo()
    {
        if (empty($this->event_id)) {
            return null;
        }
        return Videos::findByEpisode($this->event_id);
    }

    /**
     * Get the playlists in which the related video object is added to.
     *
     * @return array [\Opencast\Models\Playlists]
     */
    public function getPlaylists()
    {
        $playlists = [];
        $video = $this->getVideo();
        if (empty($video)) {
            return [];
        }
        return $video->playlists ?? [];
    }

    /**
     * Get the seminar playlist in which the video via (oc_playlist_seminar_video) is added to.
     *
     * @return array [PlaylistSeminars] or empty array
     */
    public function getSeminarPlaylists($seminar_id)
    {
        $seminar_playlists = [];
        $video = $this->getVideo();
        if (empty($video)) {
            return [];
        }

        $sql = 'INNER JOIN oc_playlist_seminar_video AS opsv ON (id = opsv.playlist_seminar_id AND opsv.video_id = :video_id) WHERE seminar_id = :seminar_id';
        $seminar_playlists = PlaylistSeminars::findBySql($sql, [
            ':video_id' => $video->id,
            ':seminar_id' => $seminar_id,
        ]);

        return $seminar_playlists ?? [];
    }
}
