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
        $playlist_data = $this->playlist->toArray();
        $playlist_data['videos_count'] = count($this->seminar_videos);
        $playlist_data['visibility'] = $this->visibility;

        if (!is_null($playlist_data['allow_download'])) {
            $playlist_data['allow_download'] = filter_var(
                $playlist_data['allow_download'],
                FILTER_VALIDATE_BOOLEAN
            );
        }

        $playlist_data['is_default'] = $this->is_default;

        return $playlist_data;
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
    public static function getUserVideosCourses() {
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
    public static function getPlaylistVideosCourses($playlist_id, $cid) {
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
}
