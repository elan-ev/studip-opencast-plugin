<?php

namespace Opencast\Models;

use Opencast\Models\SeminarSeries;

class VideoSeminars extends \SimpleORMap
{
    protected static function configure($config = [])
    {
        $config['db_table'] = 'oc_video_seminar';

        $config['belongs_to']['video'] = [
            'class_name' => 'Opencast\\Models\\Videos',
            'foreign_key' => 'video_id',
        ];

        $config['belongs_to']['course'] = [
            'class_name' => 'Course',
            'foreign_key' => 'seminar_id',
        ];

        parent::configure($config);
    }

    /**
     * Assigns a video to the seminar if the video belongs to the seminar' series
     * 
     * @Notification OpencastVideoSync
     *
     * @param string                $eventType
     * @param object                $episode
     * @param Opencast\Models\Video $video
     *
     * @return void
     */
    public static function videoSeminarEntry($eventType, $episode, $video)
    {
        // check if a series is assigned to this event
        if (!isset($episode->is_part_of) || empty($episode)) {
            return;
        }

        // get the courses this series belongs to
        $series = SeminarSeries::findBySeries_id($episode->is_part_of);
        foreach ($series as $s) {
            $video_seminar = self::findOneBySQL('video_id = ? AND seminar_id = ?', [$video->id, $s['seminar_id']]);
            if (empty($video_seminar)) {
                $video_seminar = new self();
                $video_seminar->video_id = $video->id;
                $video_seminar->seminar_id = $s['seminar_id'];
                $video_seminar->visibility = $video->visibility ? $video->visibility : 'visible';
                $video_seminar->store();
            }
        }
    }

    /**
     * Get Seminar vidoes by a list of video tokens.
     *
     * @param string $course_id Course ID
     * @param array $tokens array of video tokens
     * @return \SimpleORCollection a collection of seminar videos
     */
    public function getSeminarVideosByTokens($course_id, $tokens) {
        $sql = "INNER JOIN oc_video AS ocv ON (ocv.id = video_id) WHERE ocv.token IN (:tokens) AND seminar_id = :seminar_id";
        $params = [
            ':seminar_id' => $course_id,
            ':tokens' => $tokens
        ];
        return self::findBySQL($sql, $params);
    }
}
