<?php

namespace Opencast\Models;

class PlaylistSeminars extends \SimpleORMap
{
    protected static function configure($config = [])
    {
        $config['db_table'] = 'oc_playlist_seminar';

        $config['belongs_to']['playlist'] = [
            'class_name' => 'Opencast\\Models\\Playlists',
            'foreign_key' => 'playlist_id',
        ];

        $config['has_many']['seminar_videos'] = [
            'class_name' => 'Opencast\\Models\\PlaylistSeminarVideos',
            'assoc_foreign_key' => 'playlist_seminar_id',
        ];

        parent::configure($config);
    }

    /**
     * Get sanitized array to send to the frontend
     */
    public function toSanitizedArray()
    {
        $playlist_data = $this->playlist->toSanitizedArray();
        $playlist_data['videos_count'] = count($this->seminar_videos);
        $playlist_data['visibility'] = $this->visibility;

        // Always prioritise allow download property of seminar playlist
        if (!is_null($this->allow_download)) {
            $playlist_data['allow_download'] = $this->allow_download;
        }

        $playlist_data['is_default'] = $this->is_default;

        $playlist_data['contains_scheduled'] = (bool) $this->contains_scheduled;
        $playlist_data['contains_livestreams'] = (bool) $this->contains_livestreams;

        return $playlist_data;
    }

    /**
     * Get the courses of the passed video
     *
     * @param Videos $video
     * @return array course ids of the video
     */
    public static function getCoursesOfVideo(Videos $video) {
        $query = 'SELECT DISTINCT ops.seminar_id FROM oc_playlist_seminar AS ops'.
                ' INNER JOIN oc_playlist_video AS opv ON (opv.playlist_id = ops.playlist_id AND opv.video_id = :video_id)';

        $params = [
            ':video_id' => $video->id,
        ];

        $stmt = \DBManager::get()->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Get the courses from all videos the user has access to
     *
     * @return array
     *  [
     *      course1,
     *      course2,
     *      course3,
     *      ...
     *  ];
     */
    public static function getUserVideosCourses()
    {
        global $user, $perm;

        $query = 'SELECT DISTINCT ops.seminar_id FROM oc_playlist_seminar AS ops'.
                 ' LEFT JOIN oc_playlist_video AS opv ON (opv.playlist_id = ops.playlist_id)'.
                 ' LEFT JOIN oc_video_user_perms AS vup ON (opv.video_id = vup.video_id)';
        $params = [];

        // root can access all courses, no further checking for perms is necessary
        if (!$perm->have_perm('root')) {
            if ($perm->have_perm('admin', $user->id)) {
                $query .= ' WHERE opv.video_id IN (:video_ids)';
                $params[':video_ids'] = Videos::getFilteredVideoIds($user->id);
            } else {
                $query .= ' WHERE vup.user_id = :user_id ';
                $params[':user_id'] = $user->id;
            }
        }

        $stmt = \DBManager::get()->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Get the courses from all visible videos in a specific playlist
     *
     * @param string $playlist_id
     * @param string $cid
     *
     * @return array
     *  [
     *      course1,
     *      course2,
     *      course3,
     *      ...
     *  ];
     */
    public static function getPlaylistVideosCourses($playlist_id, $cid)
    {
        global $perm;

        $query = 'SELECT DISTINCT ops.seminar_id FROM oc_playlist_seminar AS ops'.
                 ' INNER JOIN oc_playlist_video AS opv ON (opv.playlist_id = ops.playlist_id)';
        $where = ' WHERE opv.video_id IN (SELECT opv_i.video_id FROM oc_playlist_video AS opv_i WHERE opv_i.playlist_id = :playlist_id)';
        $params = [':playlist_id' => $playlist_id];

        if (!(empty($cid) || $perm->have_perm('dozent'))) {
            $query .= ' LEFT JOIN oc_playlist_seminar_video AS opsv ON (opsv.playlist_seminar_id = ops.id AND opsv.video_id = opv.video_id)';
            $where .= ' AND (ops.seminar_id = :cid '.
                ' AND (opsv.visibility IS NULL AND opsv.visible_timestamp IS NULL AND ops.visibility = "visible"'.
                ' OR opsv.visibility = "visible" AND opsv.visible_timestamp IS NULL'.
                ' OR opsv.visible_timestamp < NOW()))';
            $params[':cid'] = $cid;
        }

        $query .= $where;
        $stmt = \DBManager::get()->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Get the courses from all playlists the user has access to
     *
     * @return array
     *  [
     *      course1,
     *      course2,
     *      course3,
     *      ...
     *  ];
     */
    public static function getUserPlaylistsCourses()
    {
        global $user;

        $query = "SELECT DISTINCT ops.seminar_id FROM oc_playlist_seminar AS ops".
                " LEFT JOIN oc_playlist_user_perms AS ocp ON (ocp.playlist_id = ops.playlist_id)".
                " WHERE ocp.user_id = :user_id".
                " AND ocp.perm IN ('owner', 'write', 'read')";
        $params[':user_id'] = $user->id;

        $stmt = \DBManager::get()->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Get the courses from all playlists of a course the user has access to
     *
     * @return array
     *  [
     *      course1,
     *      course2,
     *      course3,
     *      ...
     *  ];
     */
    public static function getCoursePlaylistsCourses(String $cid)
    {
        global $user;

        $query = "SELECT DISTINCT seminar_id FROM oc_playlist_seminar".
            " WHERE playlist_id IN (".
            " SELECT ops.playlist_id FROM oc_playlist_seminar AS ops".
            " LEFT JOIN oc_playlist_user_perms AS ocp ON (ocp.playlist_id = ops.playlist_id)".
            " WHERE seminar_id = :cid".
            " AND (ops.is_default = 1 OR ocp.user_id = :user_id AND ocp.perm IN ('owner', 'write', 'read')))";
        $params = [
            ':user_id'  => $user->id,
            ':cid'      => $cid,
        ];

        $stmt = \DBManager::get()->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Get sanitized array of courses to send to the frontend.
     * Only courses to which the user has access are returned.
     *
     * @param array $courses_ids id of courses
     * @param String|null $user_id user id
     * @return array sanitized array of courses
     */
    public static function getCoursesArray(Array $courses_ids, String $user_id = null) {
        global $perm;

        $courses = [];
        foreach ($courses_ids as $course_id) {
            $course = \Course::find($course_id);

            // Check if user has access to this seminar
            if ($perm->have_studip_perm($course_id, 'user', $user_id)) {
                $lecturers = [];
                $lecturers_obj = $course->getMembersWithStatus('dozent');
                foreach ($lecturers_obj as $lecturer) {
                    $lecturers[] = [
                        'username'    => $lecturer->username,
                        'name'  => $lecturer->getUserFullname(),
                    ];
                }

                $courses[] = [
                    'id'        => $course->id,
                    'name'      => $course->getFullname('number-name'),
                    'lecturers' => $lecturers,
                ];
            }
        }
        return $courses;
    }
}
