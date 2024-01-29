<?php

namespace Opencast\Models;

use Opencast\Models\Videos;

class Tags extends \SimpleORMap
{
    protected static function configure($config = [])
    {
        $config['db_table'] = 'oc_tags';

        parent::configure($config);
    }

    /**
     * Get the tags from all videos the user has access too
     *
     * @return array
     *  [
     *      tag1,
     *      tag2,
     *      tag3,
     *      ...
     *  ];
     */
    public static function getUserVideosTags()
    {
        global $user, $perm;

        $query = 'SELECT tag FROM oc_tags'.
                ' LEFT JOIN oc_video_tags AS vt ON (vt.tag_id = id)'.
                ' LEFT JOIN oc_video_user_perms AS vup ON (vt.video_id = vup.video_id)';
        $params = [];

        // root can access all videos, no further checking for perms is necessary
        if (!$perm->have_perm('root')) {
            if ($perm->have_perm('admin', $user->id)) {
                $query .= ' WHERE vt.video_id IN (:video_ids) ';
                $params[':video_ids'] = Videos::getFilteredVideoIDs($user->id);
            } else {
                $query .= ' WHERE vup.user_id = :user_id ';
                $params[':user_id'] = $user->id;
            }
        }

        $query .= ' GROUP BY tag';

        $stmt = \DBManager::get()->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }


    /**
     * Get the tags from all visible videos in an specific playlist
     *
     * @param string $playlist_id
     * @param string $cid
     *
     * @return array
     *  [
     *      tag1,
     *      tag2,
     *      tag3,
     *      ...
     *  ];
     */
    public static function getPlaylistVideosTags($playlist_id, $cid)
    {
        global $perm;

        $query = 'SELECT tag FROM oc_tags'.
                ' LEFT JOIN oc_video_tags AS vt ON (vt.tag_id = id)'.
                ' INNER JOIN oc_playlist_video AS opv ON (opv.video_id = vt.video_id AND opv.playlist_id = :playlist_id)';
        $params = [':playlist_id' => $playlist_id];

        if (!(empty($cid) || $perm->have_perm('dozent'))) {
            $query .= ' INNER JOIN oc_playlist_seminar AS ops ON (ops.seminar_id = :cid AND ops.playlist_id = opv.playlist_id)'.
                    ' LEFT JOIN oc_playlist_seminar_video AS opsv ON (opsv.playlist_seminar_id = ops.id AND opsv.video_id = opv.video_id)'.
                    ' WHERE (opsv.visibility IS NULL AND opsv.visible_timestamp IS NULL AND ops.visibility = "visible"'.
                    ' OR opsv.visibility = "visible" AND opsv.visible_timestamp IS NULL'.
                    ' OR opsv.visible_timestamp < NOW())';

            $params[':cid'] = $cid;
        }

        $query .= ' GROUP BY tag';

        $stmt = \DBManager::get()->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Get the tags from all visible videos in a specific course
     *
     * @param string $course_id
     *
     * @return array
     *  [
     *      tag1,
     *      tag2,
     *      tag3,
     *      ...
     *  ];
     */
    public static function getCourseVideosTags($course_id)
    {
        global $perm;

        $query = 'SELECT tag FROM oc_tags'.
                ' LEFT JOIN oc_video_tags AS vt ON (vt.tag_id = id)'.
                ' INNER JOIN oc_playlist_video AS opv ON (opv.video_id = vt.video_id)'.
                ' INNER JOIN oc_playlist_seminar AS ops ON (ops.playlist_id = opv.playlist_id AND ops.seminar_id = :cid)';
        $params = [':cid' => $course_id];

        if (!$perm->have_studip_perm('dozent', $course_id)) {
            $query .= ' LEFT JOIN oc_playlist_seminar_video AS opsv ON (opsv.playlist_seminar_id = ops.id AND opsv.video_id = opv.video_id)'.
                ' WHERE (opsv.visibility IS NULL AND opsv.visible_timestamp IS NULL AND ops.visibility = "visible"'.
                ' OR opsv.visibility = "visible" AND opsv.visible_timestamp IS NULL'.
                ' OR opsv.visible_timestamp < NOW())';
        }

        $query .= ' GROUP BY tag';

        $stmt = \DBManager::get()->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Get the tags from all playlists the user has access to
     *
     * @return array
     *  [
     *      tag1,
     *      tag2,
     *      tag3,
     *      ...
     *  ];
     */
    public static function getUserPlaylistsTags()
    {
        global $user;

        $query = "SELECT tag FROM oc_tags".
                " LEFT JOIN oc_playlist_tags AS pt ON (pt.tag_id = id)".
                " LEFT JOIN oc_playlist_user_perms AS ocp ON (pt.playlist_id = ocp.playlist_id)".
                " WHERE ocp.user_id = :user_id".
                " AND ocp.perm IN ('owner', 'write', 'read')".
                " GROUP BY tag";
        $params[':user_id'] = $user->id;

        $stmt = \DBManager::get()->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Get the tags from all playlists of a course the user has access to
     *
     * @return array
     *  [
     *      tag1,
     *      tag2,
     *      tag3,
     *      ...
     *  ];
     */
    public static function getCoursePlaylistsTags(String $cid)
    {
        global $user;

        $query = "SELECT tag FROM oc_tags".
            " INNER JOIN oc_playlist_tags AS pt ON (pt.tag_id = id)".
            " INNER JOIN oc_playlist_seminar AS ops ON (ops.playlist_id = pt.playlist_id)".
            " LEFT JOIN oc_playlist_user_perms AS ocp ON (pt.playlist_id = ocp.playlist_id)".
            " WHERE ops.seminar_id = :cid".
            " AND (ops.is_default = 1 OR ocp.user_id = :user_id AND ocp.perm IN ('owner', 'write', 'read'))".
            " GROUP BY tag";
        $params = [
            ':user_id'  => $user->id,
            ':cid'      => $cid,
        ];

        $stmt = \DBManager::get()->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function copy(String $playlist_id = null, Videos $video_id = null)
    {
        global $user;

        // Copy tag only if user not own the tag
        $tag = Tags::findOneBySQL('tag = ? AND user_id = ?', [$this->tag, $user->id]);
        if (empty($tag)) {
            $tag = Tags::create([
                'tag' => $this->tag,
                'user_id' => $user->id,
            ]);
        }

        if (!empty($playlist_id)) {
            PlaylistTags::create([
                'playlist_id' => $playlist_id,
                'tag_id' => $tag->id,
            ]);
        }

        if (!empty($video_id)) {
            VideoTags::create([
                'video_id' => $video_id,
                'tag_id' => $tag->id,
            ]);
        }
    }
}
