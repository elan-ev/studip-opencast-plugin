<?php

namespace Opencast\Models;

use Course;
use Opencast\Models\ScheduledRecordings;
use Opencast\Models\ScheduleHelper;

class WidgetHelper
{
    /**
     * Get the list of upcoming livestreams.
     *
     * @return array the upcoming livestreams:
     * [
     *      'url' => string,
     *      'date' => CourseDate,
     *      'course' => Course,
     *      'video' => Video,
     *      'playlist' => Playlist
     * ]
     */
    static function getUpcomingLivestreams()
    {
        global $perm, $user;
        $courses = [];
        if (!$perm->have_perm('admin') && !$perm->have_perm('root')) {
            $courses = Course::findBySQL(
                'INNER JOIN seminar_user AS su USING(Seminar_id)
                    INNER JOIN oc_seminar_series AS oc_sem ON seminare.Seminar_id = oc_sem.seminar_id
                    WHERE su.user_id = ? AND oc_sem.visibility = "visible" AND series_id IS NOT NULL',
                [$user->id]
            );
        }

        if (empty($courses)) {
            return [];
        }

        $upcoming_livestreams = [];
        foreach ($courses as $course) {
            $livestreams = ScheduledRecordings::getScheduleRecordingList(null, $course->id, '', true);
            if (empty($livestreams)) {
                return [[], 'NOLIVESTREAM'];
            }
            foreach ($livestreams as $livestream) {
                $start = intVal($livestream->start);
                $end = intVal($livestream->end);
                $livestream_status = ScheduleHelper::getLivestreamTimeStatus($start, $end);
                $date = \CourseDate::find($livestream->date_id);

                // Firs level checker: if date is ok and the livestream is upcoming...
                if (!empty($date) && $livestream_status == ScheduleHelper::LIVESTREAM_STATUS_SCHEDULED) {
                    $seminar_playlists = $livestream->getSeminarPlaylists($course->id);
                    $video = $livestream->getVideo();

                    // Second level checker: if there is any seminar playlist attacked to this livestream video!
                    if (!empty($seminar_playlists) && $video->is_livestream) {
                        foreach ($seminar_playlists as $seminar_playlist) {

                            // Third level checker: if the seminar playlist is visible.
                            if ($seminar_playlist->visibility == 'visible') {

                                // Forth level checker: if there is any seminar playlist video and if it is visible!
                                $sql = 'visibility = "visible" AND visible_timestamp IS NULL OR visible_timestamp < NOW()';
                                $sql .= ' AND playlist_seminar_id = ? AND video_id = ?';
                                $seminar_playlist_video = PlaylistSeminarVideos::findOneBySQL($sql, [
                                    $seminar_playlist->id,
                                    $video->id
                                ]);
                                // To avoid redundancy, we also check if the upcoming array contains the date id.
                                if (!empty($seminar_playlist_video) && !isset($upcoming_livestreams[$date->id])) {
                                    $url_with_playlist_token = \PluginEngine::getURL(
                                        'opencast',
                                        ['cid' => $course->id],
                                        'course#/course/videos?taget_pl_token=' . $seminar_playlist->playlist->token
                                    );
                                    $upcoming_livestreams[$date->id] = [
                                        'url' => $url_with_playlist_token,
                                        'date' => $date,
                                        'course' => $course,
                                        'video' => $video,
                                        'playlist' => $seminar_playlist->playlist
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }
        return $upcoming_livestreams;
    }
}
