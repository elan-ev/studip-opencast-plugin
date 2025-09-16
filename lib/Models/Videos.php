<?php

namespace Opencast\Models;

use Opencast\Errors\Error;
use Opencast\Helpers\PlaylistMigration;
use Opencast\Models\Tags;
use Opencast\Models\Playlists;
use Opencast\Models\REST\ApiEventsClient;
use Opencast\Models\REST\ApiWorkflowsClient;
use Opencast\Models\Helpers;
use Opencast\Models\ScheduledRecordings;

class Videos extends UPMap
{
    /**
     * @inheritDoc
     */
    protected static function configure($config = [])
    {
        $config['db_table'] = 'oc_video';

        $config['has_many']['perms'] = [
            'class_name' => 'Opencast\\Models\\VideosUserPerms',
            'assoc_foreign_key' => 'video_id',
        ];

        $config['has_many']['shares'] = [
            'class_name' => 'Opencast\\Models\\VideosShares',
            'assoc_foreign_key' => 'video_id',
        ];

        $config['has_and_belongs_to_many']['tags'] = [
            'class_name'     => 'Opencast\\Models\\Tags',
            'thru_table'     => 'oc_video_tags',
            'thru_key'       => 'video_id',
            'thru_assoc_key' => 'tag_id'
        ];

        $config['has_and_belongs_to_many']['playlists'] = [
            'class_name'     => 'Opencast\\Models\\Playlists',
            'thru_table'     => 'oc_playlist_video',
            'thru_key'       => 'video_id',
            'thru_assoc_key' => 'playlist_id',
        ];

        $config['belongs_to']['config'] = [
            'class_name' => 'Opencast\Models\Config',
            'foreign_key' => 'config_id',
        ];

        parent::configure($config);
    }

    /**
     * Returns the list of videos that are accessable. These can then be used to narrow it down by search filters
     * @info: this method is called outside of this Model class, therefore, it needs to be public.
     * @param string $user_id
     *
     * @return array the video ids
     */
    public static function getFilteredVideoIDs($user_id)
    {
        // get all courses and their playlists this user has access to. Only courses with activated OC plugin are included
        $courses = Helpers::getMyCourses($user_id);

        if (empty($courses)) {
            return [];
        }

        $stmt = \DBManager::get()->prepare($sql = 'SELECT oc_video.id FROM oc_video
            JOIN oc_playlist_seminar ON (oc_playlist_seminar.seminar_id IN (:courses))
            JOIN oc_playlist         ON (oc_playlist_seminar.playlist_id = oc_playlist.id)
            JOIN oc_playlist_video   ON (oc_playlist.id = oc_playlist_video.playlist_id AND oc_video.id = oc_playlist_video.video_id)
            WHERE 1
        ');

        $stmt->bindValue(':courses', $courses, \StudipPDO::PARAM_ARRAY);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Get videos for the passed playlist narrowed down by optional filters.
     * This method does no further check for permissions and assumes the current user has access to the playlist!
     *
     * @param string $playlist_id
     * @param Opencast\Models\Filter $filters
     *
     * @return array
     *    [
     *        'videos' => [Opencast\Models\Videos],
     *        'sql'    => '...,
     *        'count'  => 123
     *    ];
     */
    public static function getPlaylistVideos($playlist_id, $filters)
    {
        global $perm;

        $sql = ' INNER JOIN oc_playlist_video AS opv ON (opv.playlist_id = :playlist_id AND opv.video_id = oc_video.id)';
        $where = ' WHERE 1 ';
        $params = [':playlist_id' => $playlist_id];

        $cid = $filters->getCourseId();
        $required_course_perm = \Config::get()->OPENCAST_TUTOR_EPISODE_PERM ? 'tutor' : 'dozent';
        if (!(empty($cid) || $perm->have_studip_perm($required_course_perm, $cid))) {
            $sql .= ' INNER JOIN oc_playlist_seminar AS ops ON (ops.seminar_id = :cid AND ops.playlist_id = opv.playlist_id)'.
                    ' LEFT JOIN oc_playlist_seminar_video AS opsv ON (opsv.playlist_seminar_id = ops.id AND opsv.video_id = opv.video_id)';

            $where = ' WHERE '. self::getVisibilitySql($cid);

            $params[':cid'] = $cid;
        }

        $query = [
            'sql'   => $sql,
            'where' => $where,
            'params' => $params
        ];

        return self::getFilteredVideos($query, $filters);
    }

    /**
     * Get videos for the passed course narrowed down by optional filters.
     * This method does not further check for permissions and assumes the current user has access to the course!
     *
     * @param string $course_id
     * @param Opencast\Models\Filter $filters
     *
     * @return array
     *    [
     *        'videos' => [Opencast\Models\Videos],
     *        'sql'    => '...,
     *        'count'  => 123
     *    ];
     */
    public static function getCourseVideos($course_id, $filters)
    {
        global $perm;

        $sql = ' INNER JOIN oc_playlist_video AS opv ON (opv.video_id = oc_video.id)
                 INNER JOIN oc_playlist_seminar AS ops ON (ops.playlist_id = opv.playlist_id AND ops.seminar_id = :cid)';
        $where = ' WHERE 1 ';
        $params = [':cid' => $course_id];

        $required_course_perm = \Config::get()->OPENCAST_TUTOR_EPISODE_PERM ? 'tutor' : 'dozent';
        if (!$perm->have_studip_perm($required_course_perm, $course_id)) {
            $sql .= ' LEFT JOIN oc_playlist_seminar_video AS opsv ON (opsv.playlist_seminar_id = ops.id AND opsv.video_id = opv.video_id)';

            $where = ' WHERE '. self::getVisibilitySql($course_id);
        }

        $query = [
            'sql'   => $sql,
            'where' => $where,
            'params' => $params
        ];

        return self::getFilteredVideos($query, $filters);
    }

    private static function getVisibilitySql($course_id)
    {
        // if each video has to explicitly set to visible, filter out everything else
        $course_hide_episodes = \Config::get()->OPENCAST_HIDE_EPISODES;
        $course_default_episodes_visibility = \CourseConfig::get($course_id)->OPENCAST_COURSE_DEFAULT_EPISODES_VISIBILITY ?? 'default';
        if ($course_default_episodes_visibility !== 'default') {
            $course_hide_episodes = $course_default_episodes_visibility === 'hidden' ? true : false;
        }
        if ($course_hide_episodes) {
            return '(
                (opsv.visibility = "visible" AND opsv.visible_timestamp IS NULL)
                OR (opsv.visible_timestamp < NOW())
            )';
        } else {
            return '(
                (opsv.visibility IS NULL AND opsv.visible_timestamp IS NULL AND ops.visibility = "visible")
                OR (opsv.visibility = "visible" AND opsv.visible_timestamp IS NULL)
                OR (opsv.visible_timestamp < NOW())
            )';
        }
    }

    /**
     * Get the list of videos where the user has owner perm, faceted by the passed filters
     *
     * @param Opencast\Models\Filter $filters
     * @param string $user_id
     *
     * @return array
     *   [
     *       'videos' => [Opencast\Models\Videos],
     *       'count'  => int
     *   ];
     */
    public static function getUserVideos($filters, $user_id = null)
    {
        global $user, $perm;

        if (!$user_id) {
            $user_id = $user->id;
        }

        $sql    = ' LEFT JOIN oc_video_user_perms AS p ON (p.video_id = oc_video.id)';
        $params = [];
        $where  = ' WHERE 1 ';

        // root can access all videos, no further checking for perms is necessary
        if (!$perm->have_perm('root')) {
            $params = [
                ':user_id'=> $user_id
            ];

            // Show video where the user has owner perm
            $sql  = ' INNER JOIN oc_video_user_perms AS p ON (p.user_id = :user_id AND p.video_id = oc_video.id) ';
            $where = " WHERE p.perm = 'owner'";
        }

        return self::getFilteredVideos([
            'sql'    => $sql,
            'where'  => $where,
            'params' => $params
        ], $filters);
    }

    /**
     * Get the list of accessible videos by explicit perms or by course lecturer memberships, faceted by the passed filters
     *
     * @param Opencast\Models\Filter $filters
     * @param string $user_id
     *
     * @return array
     *   [
     *       'videos' => [Opencast\Models\Videos],
     *       'count'  => int
     *   ];
     */
    public static function getCoursewareVideos($filters, $user_id = null)
    {
        global $user, $perm;

        if (!$user_id) {
            $user_id = $user->id;
        }

        $sql    = ' LEFT JOIN oc_video_user_perms AS p ON (p.video_id = oc_video.id)';
        $params = [];
        $where  = ' WHERE 1 ';

        // root can access all videos, no further checking for perms is necessary
        if (!$perm->have_perm('root')) {
            $params = [
                ':user_id'=> $user_id
            ];

            $where = ' WHERE (oc_video.id IN (:video_ids) OR p.user_id = :user_id)';
            $params[':video_ids'] = self::getFilteredVideoIDs($user_id);
        }

        return self::getFilteredVideos([
            'sql'    => $sql,
            'where'  => $where,
            'params' => $params
        ], $filters);
    }

    /**
     * Add filters to the passed data and return videos.
     *
     * @param array $query
     *    [
     *        'sql'   => '',
     *        'where' => '',
     *        'params' => ''
     *    ];
     * @param Opencast\Models\Filter $filters
     *
     * @return array
     *    [
     *        'videos' => [Opencast\Models\Videos],
     *        'count'  => 123
     *    ];
     */
    protected static function getFilteredVideos($query, $filters)
    {
        global $perm;

        $sql    = $query['sql'];
        $where  = $query['where'];
        $params = $query['params'];

        $tag_ids      = [];
        $playlist_ids = [];
        $course_ids   = [];
        $lecturer_ids = [];

        foreach ($filters->getFilters() as $filter) {
            switch ($filter['type']) {
                case 'text':
                    $pname = ':text' . sizeof($params);
                    $where .= " AND (title LIKE $pname OR description LIKE $pname)";
                    $params[$pname] = '%' . $filter['value'] .'%';
                    break;

                case 'tag':
                    // get id of this tag (if any)
                    if (!empty($filter['value'])) {
                        $tags = Tags::findBySQL($sq = 'tag LIKE ?',  [$filter['value']]);

                        if (!empty($tags)) {
                            $tag_ids[$filter['value']] = [
                                'tag_ids' => array_map(function ($tag) {
                                    return $tag->id;
                                }, $tags),
                                'compare' => $filter['compare'],
                            ];
                        } else {
                            $tag_ids[] = '-1';
                        }
                    }
                    break;

                case 'playlist':
                    $playlist = Playlists::findOneByToken($filter['value']);

                    // check, if user can access this playlist
                    if (!empty($playlist) && $playlist->getUserPerm()) {
                        $playlist_ids[$playlist->id] = [
                            'id' => $playlist->id,
                            'compare' => $filter['compare']
                        ];
                    } else {
                        $playlist_ids[] = '-1';
                    }

                    break;

                case 'course':
                    $course = \Course::find($filter['value']);

                    // check, if user has access to this seminar
                    if (!empty($course) && $perm->have_studip_perm('user', $course->id)) {
                        $course_ids[$course->id] = [
                            'id' => $course->id,
                            'compare' => $filter['compare']
                        ];
                    } else {
                        $course_ids[] = '-1';
                    }

                    break;

                case 'lecturer':
                    $lecturer = \User::findByUsername($filter['value']);

                    if (!empty($lecturer)) {
                        $lecturer_ids[$lecturer->user_id] = [
                            'id' => $lecturer->user_id,
                            'compare' => $filter['compare']
                        ];
                    } else {
                        $lecturer_ids[] = '-1';
                    }

                    break;
            }
        }


        if (!empty($tag_ids)) {
            foreach ($tag_ids as $value => $tag_filter) {
                $tags_param = ':tags' . $value;
                $params[$tags_param] = $tag_filter['tag_ids'];
                if ($tag_filter['compare'] == '=') {
                    $sql .= ' INNER JOIN oc_video_tags AS t'. $value .' ON (t'. $value .'.video_id = oc_video.id '
                        .' AND t'. $value .'.tag_id IN ('. $tags_param .'))';
                } else {
                    $sql .= ' LEFT JOIN oc_video_tags AS t'. $value .' ON (t'. $value .'.video_id = oc_video.id '
                        .' AND t'. $value .'.tag_id IN ('. $tags_param .'))';

                    $where .= ' AND t'. $value . '.tag_id IS NULL ';
                }
            }
        }

        if (!empty($playlist_ids)) {
            foreach ($playlist_ids as $playlist_id) {
                if ($playlist_id['compare'] == '=') {
                    $sql .= ' INNER JOIN oc_playlist_video AS opv'. $playlist_id['id'] .' ON (opv'. $playlist_id['id'] .'.video_id = oc_video.id '
                        .' AND opv'. $playlist_id['id'] .'.playlist_id = '. $playlist_id['id'] .')';
                } else {
                    $sql .= ' LEFT JOIN oc_playlist_video AS opv'. $playlist_id['id'] .' ON (opv'. $playlist_id['id'] .'.video_id = oc_video.id '
                        .' AND opv'. $playlist_id['id'] .'.playlist_id = '. $playlist_id['id'] .')';

                    $where .= ' AND opv'. $playlist_id['id'] . '.playlist_id IS NULL ';
                }
            }
        }

        if (!empty($course_ids)) {
            foreach ($course_ids as $course_id) {
                if ($course_id['compare'] == '=') {
                    $sql .= " INNER JOIN oc_playlist_video AS opv". $course_id['id'] ." ON (opv". $course_id['id'] .".video_id = oc_video.id)"
                        . " INNER JOIN oc_playlist_seminar AS ops". $course_id['id'] ." ON "
                        ." (ops". $course_id['id'] .".playlist_id = opv". $course_id['id'] .".playlist_id "
                        ." AND ops". $course_id['id'] .".seminar_id = '". $course_id['id'] ."')";
                } else {
                    $where .= " AND '". $course_id['id']."' NOT IN ("
                        ." SELECT DISTINCT ops". $course_id['id'].".seminar_id FROM oc_playlist_seminar AS ops". $course_id['id']
                        ." INNER JOIN oc_playlist_video AS ocp". $course_id['id']
                        ." ON (ocp". $course_id['id'].".playlist_id = ops". $course_id['id'].".playlist_id "
                        ." AND ocp". $course_id['id'].".video_id = oc_video.id))";
                }
            }
        }

        if (!empty($lecturer_ids)) {
            foreach ($lecturer_ids as $lecturer_id) {
                if ($lecturer_id['compare'] == '=') {
                    $sql .= " INNER JOIN oc_playlist_video AS opv". $lecturer_id['id'] ." ON (opv". $lecturer_id['id'] .".video_id = oc_video.id)"
                        ." INNER JOIN oc_playlist_seminar AS ops". $lecturer_id['id'] ." ON "
                        ." (ops". $lecturer_id['id'] .".playlist_id = opv". $lecturer_id['id'] .".playlist_id)";
                    $where .= " AND ops". $lecturer_id['id'] .".seminar_id IN ("
                        ." SELECT DISTINCT su". $lecturer_id['id'] .".Seminar_id FROM seminar_user AS su". $lecturer_id['id']
                        ." WHERE su". $lecturer_id['id'] .".user_id = '". $lecturer_id['id']. "' AND su". $lecturer_id['id'] .".status = 'dozent')";
                } else {
                    $where .= " AND oc_video.id NOT IN (SELECT DISTINCT ov". $lecturer_id['id'] .".id FROM oc_video AS ov". $lecturer_id['id']
                        ." INNER JOIN oc_playlist_video AS opv". $lecturer_id['id'] ." ON (opv". $lecturer_id['id'] .".video_id = ov". $lecturer_id['id'] .".id)"
                        ." INNER JOIN oc_playlist_seminar AS ops". $lecturer_id['id'] ." ON (ops". $lecturer_id['id'] .".playlist_id = opv". $lecturer_id['id'] .".playlist_id)"
                        ." INNER JOIN seminar_user AS su". $lecturer_id['id'] ." ON (su". $lecturer_id['id'] .".Seminar_id = ops". $lecturer_id['id'] .".seminar_id)"
                        ." WHERE su". $lecturer_id['id'] .".user_id = '". $lecturer_id['id'] ."' AND su". $lecturer_id['id'] .".status = 'dozent')";
                }
            }
        }

        // Only show videos with active server
        $sql .= ' INNER JOIN oc_config ON (oc_config.id = oc_video.config_id AND oc_config.active = 1)';

        $where .= " AND trashed = " . $filters->getTrashed();
        $where .= " AND oc_video.token IS NOT NULL";

        $sql .= $where;

        $sql .= ' GROUP BY oc_video.id';

        $stmt = \DBManager::get()->prepare($s = "SELECT COUNT(*) FROM (SELECT oc_video.* FROM oc_video $sql) t");
        $stmt->execute($params);
        $count = $stmt->fetchColumn();

        // TODO implement custom order
        [$field, $order] = explode("_", $filters->getOrder());

        if ($field === 'order') {
            if ($filters->getPlaylist() !== null) {
                $sql .= ' ORDER BY opv.' . $field . ' ' . $order;
            }
        } else {
            $sql .= ' ORDER BY oc_video.' . $field . ' ' . $order;
        }

        if ($filters->getLimit() != -1) {
            $sql   .= ' LIMIT '. $filters->getOffset() .', '. $filters->getLimit();
        }

        return [
            'videos' => self::findBySQL($sql, $params),
            'count'  => $count
        ];
    }

    /**
     * Count number of new videos in a course since last visit
     *
     * @param string    $course_id  course id
     * @param int       $last_visit time of last visit
     * @param string    $user_id    user id
     *
     * @return int number of new videos
     */
    public static function getNumberOfNewCourseVideos($course_id, $last_visit, $user_id = null) {
        global $perm;

        $sql = 'SELECT COUNT(DISTINCT video.id)
                FROM `oc_video` AS video
                INNER JOIN `oc_playlist_video` AS opv ON (opv.video_id = video.id)
                INNER JOIN `oc_playlist` AS op ON (op.id = opv.playlist_id)
                INNER JOIN `oc_playlist_seminar` AS ops ON (ops.playlist_id = op.id)';

        $where = 'WHERE ops.seminar_id = :course_id
                  AND (UNIX_TIMESTAMP(video.mkdate) > :last_visit OR UNIX_TIMESTAMP(opv.mkdate) > :last_visit)
                  AND video.trashed = 0
                  AND video.token IS NOT NULL
                  AND video.state IS NULL
                  AND video.available = 1';

        if (!$perm->have_perm('dozent', $user_id)) {
            $sql .= ' LEFT JOIN oc_playlist_seminar_video AS opsv ON (opsv.playlist_seminar_id = ops.id AND opsv.video_id = opv.video_id)';

            $where .= ' AND '. self::getVisibilitySql($course_id);
        }

        $sql .= $where;

        $stmt = \DBManager::get()->prepare($sql);

        $stmt->execute([
            'course_id' => $course_id,
            'last_visit' => $last_visit,
        ]);

        return (int) $stmt->fetchColumn();
    }

    public static function findByToken($token)
    {
        return self::findOneBySQL('token = ?', [$token]);
    }

    public static function findById($id)
    {
        return self::findOneBySQL('id = ?', [$id]);
    }

    public static function findByEpisode($episode_id)
    {
        return self::findOneBySQL('episode = ?', [$episode_id]);
    }

    public function toSanitizedArray($cid = '', $playlist_id = '')
    {
        $data = $this->toArray();

        $data['chdate'] = ($data['chdate'] == '0000-00-00 00:00:00')
            ? 0 : \strtotime($data['chdate']);

        $data['mkdate'] = ($data['mkdate'] == '0000-00-00 00:00:00')
            ? 0 : \strtotime($data['mkdate']);

        // Based on the change to the preview structure, we are not dealing with preview object (json encoded) here anymore, but an URL in string format.
        $data['preview']     = $data['preview'] ?: null;
        $data['publication'] = json_decode($data['publication'], true);

        $data['perm']                 = $this->getUserPerm();
        $data['playlists']            = $this->getPlaylists();
        $data['seminar_visibility']   = $this->getSeminarVisibility($cid, $playlist_id);
        $data['video_user_available'] = true;
        $data['owner']                = $this->getOwner();

        // get availability for this video in this playlist
        if (!empty($playlist_id)) {
            $pv = PlaylistVideos::findOneBySQL('playlist_id = ? AND video_id = ?', [
                $playlist_id, $this->id
            ]);

            $data['video_user_available'] = (bool)$pv->available;
        }

        $data['tags'] = $this->tags->toArray();

        $data['trashed'] = $this->trashed ? true : false;

        if ((bool) $this->is_livestream) {
            $scheduled_recording_obj = ScheduledRecordings::findOneBySQL('event_id = ? AND is_livestream = 1',[
                $this->episode
            ]);
            if (!empty($scheduled_recording_obj)) {
                $data['livestream'] = [
                    'start' => $scheduled_recording_obj->start,
                    'end' => $scheduled_recording_obj->end,
                ];
            }
        }

        unset($data['is_livestream']);

        return $data;
    }

    private function getPlaylists()
    {
        $playlists = [];
        if (!empty($this->playlists)) {

            foreach ($this->playlists as $playlist) {
                $playlists[] = $playlist->toSanitizedArray();
            }
        }

        return $playlists;
    }

    private function getSeminarVisibility($cid, $playlist_id)
    {
        if (!empty($cid) && !empty($playlist_id)) {
            $psv = PlaylistSeminarVideos::findOneBySQL(
                "LEFT JOIN oc_playlist_seminar AS ops ON ops.id = playlist_seminar_id
                WHERE video_id = ?
                AND playlist_id = ?
                AND seminar_id = ?",
                [$this->id, $playlist_id, $cid]);

            if (!empty($psv)) {
                return [
                    'visibility'        => $psv->getValue('visibility'),
                    'visible_timestamp' => $psv->getValue('visible_timestamp')
                ];
            }
        }
        return null;
    }

    /**
     * Gets the perm value related to this video for the current user.
     *
     * @return string $perm the perm value
     */
    private function getUserPerm($user_id = null)
    {
        // Important: The order of checks from high to low is crucial to get the highest user permission
        global $user, $perm;

        if (!$user_id) {
            $user_id = $user->id;
        }

        if ($perm->have_perm('root', $user_id)) {
            return 'owner';
        }

        // get explicit user perm
        $uperm = VideosUserPerms::findOneBySQL('video_id = ? AND user_id = ?', [$this->id, $user_id]);

        // check first highest explicit permissions
        if ($uperm) {
            if ($uperm->perm == 'owner') {
                return 'owner';
            } elseif ($uperm->perm == 'write') {
                return 'write';
            }
        }

        // check if user has write permission on this video due to course ownership
        $required_course_perm = \Config::get()->OPENCAST_TUTOR_EPISODE_PERM ? 'tutor' : 'dozent';
        if ($this->haveCoursePerm($required_course_perm, $user_id)) {
            return 'write';
        }

        // check if read perms are present due to course participation
        if ($this->haveCoursePerm('user', $user_id)) {
            return 'read';
        }

        // check remaining explicit permissions
        if ($uperm) {
            if ($uperm->perm == 'read') {
                return 'read';
            } elseif ($uperm->perm == 'share') {
                return 'share';
            }
        }

        // user has no perms on this video
        return false;
    }

    private function getOwner()
    {
        $video_user_perms = VideosUserPerms::findOneBySQL('video_id = ? AND perm = ?', [$this->id, 'owner']);
        if (!$video_user_perms) {
            return null;
        }
        $uid = $video_user_perms->toSanitizedArray()['user_id'];
        $user = \User::find($uid);

        return ['id' => $user->id, 'fullname' => $user->getFullname(), 'username' => $user->username];
    }

    /**
     * Check if current user has at least given permission on the video
     *
     * @param string $required_perm required permission: read, write, owner
     * @return bool user has at least provided permission on the video
     */
    public function havePerm($required_perm, $user_id = null)
    {
        $uperm = $this->getUserPerm($user_id);

        // Check if required perm is included in user perm
        if ($required_perm == 'owner' && $uperm == 'owner') {
            return true;
        }

        if ($required_perm == 'write' && in_array($uperm, ['write', 'owner'])) {
            return true;
        }

        if ($required_perm == 'read' && in_array($uperm, ['read', 'write', 'owner'])) {
            return true;
        }

        return false;
    }

    /**
     * Check if current user has permission for a course of this video
     */
    public function haveCoursePerm(string $course_perm, string $user_id = null)
    {
        global $user, $perm;

        if (!$user_id) {
            $user_id = $user->id;
        }

        $video_courses = PlaylistSeminars::getCoursesOfVideo($this);

        if (!empty($video_courses)) {
            foreach ($video_courses as $video_course_id) {
                if ($perm->have_studip_perm($course_perm, $video_course_id, $user_id)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Updates the metadata related to this video in both opencast and local and runs republish-metadata workflow
     *
     * @param object $event the updated version of the event
     *
     * @return boolean the result of updating process
     */
    public function updateMetadata($event)
    {
        $api_event_client = ApiEventsClient::getInstance($this->config_id);

        // Only allow updating of metadata if event has publications
        $oc_event = $api_event_client->getEpisode($this->episode);

        if (!Helpers::canEventRunWorkflow($oc_event, $this)) {
            return false;
        }

        $allowed_metadata_fields = ['title', 'presenters', 'contributors',
            'subject', 'language', 'description', 'startDate'];
        $metadata = [];

        foreach ($allowed_metadata_fields as $field_name) {
            if (isset($event[$field_name])) {
                $value = $event[$field_name];
                $id = $field_name;
                if ($field_name == 'subject') {
                    $id = 'subjects';
                    $value = [$value];
                }
                if ($field_name == 'presenters') {
                    $id = 'creator';
                    $value = array_map('trim', explode(',', $value));
                }
                if ($field_name == 'contributors') {
                    $id = 'contributor';
                    $value = array_map('trim', explode(',', $value));
                }

                $metadata[] = [
                    'id' => $id,
                    'value' => $value
                ];
            }
        }

        $success = false;
        $response = $api_event_client->updateMetadata($this->episode, $metadata);
        $republish = in_array($response['code'], [200, 204]) === true;

        if ($republish) {
            $api_wf_client = ApiWorkflowsClient::getInstance($this->config_id);

            if ($api_wf_client->republish($this->episode)) {
                $success = true;
                $store_data = [];

                foreach ($allowed_metadata_fields as $field_name) {
                    if (isset($event[$field_name])) {
                        $store_data[$field_name] = $event[$field_name];
                    }
                }

                if (!empty($store_data)) {
                    $this->setData($store_data);
                    $success = $this->store() !== false;
                }
            }
        }

        if (!$success) {
            return $response;
        }

        return $success;
    }

    /**
     * Removes a video from both opencsat and local sides.
     *
     * @return boolean the result of deletion process
     */
    public function removeVideo()
    {
        $api_event_client = ApiEventsClient::getInstance($this->config_id);

        // if the video exists in opencast, make sure it is deleted
        if ($this->episode && $api_event_client->getEpisode($this->episode)) {
            if ($api_event_client->deleteEpisode($this->episode)) {
                return $this->delete();
            } else {
                return false;
            }
        }

        // if its not there anymore, just remove it from Stud.IP
        return $this->delete();
    }

    public function setWorldVisibility($visibility)
    {
        // get current ACL for event in Opencast
        $api_client  = ApiEventsClient::getInstance($this->config_id);
        $current_acl = $api_client->getAcl($this->episode);

        if (empty($current_acl)) {
            return false;
        }

        // check if ACL contains ROLE_ANONYMOUS
        $has_anonymous_role = Helpers::isWorldReadable($current_acl);

        if ($visibility === 'public' && $this->visibility !== 'public') {
            if (!$has_anonymous_role) {
                // Add ROLE_ANONYMOUS to the ACL
                $current_acl[] = [
                    'allow'  => true,
                    'role'   => 'ROLE_ANONYMOUS',
                    'action' => 'read'
                ];

                // Update the ACL in Opencast
                if ($api_client->setACL($this->episode, $current_acl)) {
                    // Update local visibility
                    $this->visibility = 'public';
                    $this->store();

                    return true;
                }

                return false;
            }
        } else if ($visibility !== 'public' && $this->visibility === 'public') {
            if ($has_anonymous_role) {
                // Remove ROLE_ANONYMOUS from the ACL
                $current_acl = array_filter($current_acl, function($acl_entry) {
                    return $acl_entry['role'] !== 'ROLE_ANONYMOUS';
                });

                // Update the ACL in Opencast
                if ($api_client->setACL($this->episode, $current_acl)) {
                    // Update local visibility
                    $this->visibility = $visibility;
                    $this->store();

                    return true;
                }

                return false;
            }
        }

        return true;
    }

    /**
     * Check that the episode has its unique ACL and set it if necessary
     *
     * @Notification OpencastVideoSync
     *
     * @param string|null $eventType
     * @param object|null $episode
     * @param Videos      $video
     *
     * @return void
     */
    public static function checkEventACL(?string $eventType, ?object $episode, Videos $video)
    {
        // Fetch episode if null
        if (empty($episode)) {
            $api_client  = ApiEventsClient::getInstance($video->config_id);
            $episode = $api_client->getEpisode($video->episode, [
                'withacl' => 'true'
            ]);
        }

        // Only allow updating of metadata if event has publications
        if (!Helpers::canEventRunWorkflow($episode, $video)) {
            return;
        }

        return $video->updateAcl(json_decode(json_encode($episode->acl), true));
    }

    /**
     * Update the ACL in Opencast if necessary
     *
     * @param string $current_acl The current ACL in Opencast
     *
     * @return bool whether ACL was updated or not
     */
    public function updateAcl($current_acl = null)
    {
        $api_client  = ApiEventsClient::getInstance($this->config_id);
        if (empty($current_acl)) {
            $current_acl = $api_client->getAcl($this->episode);
        }

        // prevent updating acl if something went wrong
        if (!is_array($current_acl)) {
            return;
        }

        $acl = [];
        if (Helpers::isWorldReadable($current_acl)) {
            $this->visibility = 'public';
        } else {
            $this->visibility = 'internal';
        }
        $this->store();

        // Don't set event id roles if episode id role access is activated by user
        if (!$this->config->settings['episode_id_role_access'] ?? true) {
            // one ACL for reading AND for reading and writing
            $acl = [
                [
                    'allow'  => true,
                    'role'   => $this->episode .'_read',
                    'action' => 'read'
                ],

                [
                    'allow'  => true,
                    'role'   => $this->episode .'_write',
                    'action' => 'read'
                ],

                [
                    'allow'  => true,
                    'role'   => $this->episode .'_write',
                    'action' => 'write'
                ]
            ];
        }

        $courses = [];

        // add course acls
        foreach ($this->playlists as $playlist) {
            $courses = array_merge($courses, $playlist->courses->pluck('id'));
        }

        $courses = array_unique($courses);

        $acl = array_merge($acl, Helpers::createACLsForCourses($courses));

        sort($acl);

        $oc_acls = Helpers::filterACLs($current_acl, $acl);

        if ($acl <> $oc_acls['studip']) {
            $new_acl = array_merge($oc_acls['other'], $acl);

            return $api_client->setACL($this->episode, $new_acl);
        }

        return false;
    }

    /**
     * Extract data from the OC event and add it to the videos db entry
     *
     * @Notification OpencastVideoSync
     *
     * @param string                $eventType
     * @param object                $episode
     * @param Opencast\Models\Video $video
     *
     * @return void
     */
    public static function parseEvent($eventType, $episode, $video)
    {
        if (!empty($episode->publications[0]->attachments)) {
            $preview               = false;
            $presenter_download    = [];
            $presentation_download = [];
            $audio_download        = [];
            $annotation_tool       = false;
            $duration              = $video->duration;
            $track_link            = '';
            $livestream_link       = '';

            $possible_previews = [
                'presentation/search+preview',
                'presentation/player+preview',
                'presenter/search+preview',
                'presenter/player+preview',
            ];

            foreach ($possible_previews as $preview_type) {
                foreach ((array) $episode->publications[0]->attachments as $attachment) {
                    if (!empty($attachment->flavor) && $attachment->flavor === $preview_type) {
                        $preview = $attachment->url;
                        break;
                    }
                }

                if (!empty($preview)) break;
            }

            foreach ($episode->publications[0]->media as $track) {
                $parsed_url = parse_url($track->url);

                if (strpos($track->flavor, 'presenter/') === 0) {
                    if (($track->mediatype === 'video/mp4' || $track->mediatype === 'video/avi')
                        && ((in_array('atom', $track->tags) || in_array('engage-download', $track->tags))
                            && $parsed_url['scheme'] != 'rtmp' && $parsed_url['scheme'] != 'rtmps')
                        && !empty($track->has_video)
                    ) {
                        $quality = self::calculateSize(
                            $track->bitrate,
                            $track->duration
                        );
                        $presenter_download[$quality] = [
                            'url'  => $track->url,
                            'info' => self::getResolutionString($track->width, $track->height)
                        ];

                        $duration = $track->duration;
                    }

                    if (
                        in_array($track->mediatype, ['audio/aac', 'audio/mp3', 'audio/mpeg', 'audio/m4a', 'audio/ogg', 'audio/opus'])
                        && !empty($track->has_audio)
                    ) {
                        $quality = self::calculateSize(
                            $track->bitrate,
                            $track->duration
                        );
                        $audio_download[$quality] = [
                            'url'  => $track->url,
                            'info' => round($track->audio->bitrate / 1000, 1) . 'kb/s, ' . explode('/', $track->mediatype)[1]
                        ];

                        $duration = $track->duration;
                    }
                }

                if ((strpos($track->flavor, 'presentation/') === 0) && (
                    ($track->mediatype === 'video/mp4'
                        || $track->mediatype === 'video/avi'
                    ) && (
                        (in_array('atom', $track->tags)
                            || in_array('engage-download', $track->tags)
                        )
                        && $parsed_url['scheme'] != 'rtmp'
                        && $parsed_url['scheme'] != 'rtmps'
                    )
                    && !empty($track->has_video)
                )) {
                    $quality = self::calculateSize(
                        $track->bitrate,
                        $track->duration
                    );

                    $presentation_download[$quality] = [
                        'url'  => $track->url,
                        'info' => self::getResolutionString($track->width, $track->height)
                    ];
                }
            }

            foreach ($episode->publications as $publication) {
                if ($publication->channel == 'engage-player') {
                    $track_link = $publication->url;
                }
                if ($publication->channel == 'annotation-tool') {
                    $annotation_tool = $publication->url;
                }
                if ($publication->channel == 'engage-live' && isset($publication->url)) {
                    $livestream_link = $publication->url;
                }
            }

            ksort($presenter_download);
            ksort($presentation_download);
            ksort($audio_download);

            $video->duration = $duration;

            // fill other metadata from event
            $video->subject      = implode(', ', (array)$episode->subjects);
            $video->presenters   = implode(', ', (array)$episode->presenter);
            $video->contributors = implode(', ', (array)$episode->contributor);

            $video->preview = $preview;

            $video->publication = json_encode([
                'downloads' => [
                    'presenter'    => $presenter_download,
                    'presentation' => $presentation_download,
                    'audio'        => $audio_download
                ],
                'annotation_tool'  => $annotation_tool,
                'track_link'       => $track_link,
                'livestream_link'  => $livestream_link,
            ]);

            return $video->store();
        }

        return false;
    }

    /**
     * Calculates the size of a track
     *
     * @param int $bitrate the bit rate of a track
     * @param int $duration the duration of a track
     *
     * @return int size of a track
     */
    private static function calculateSize($bitrate, $duration)
    {
        return ($bitrate / 8) * ($duration / 1000);
    }

    /**
     * Get the resolution in string format
     *
     * @param int $width the width of a track
     * @param int $height the height of a track
     *
     * @return string resolution string
     */
    private static function getResolutionString($width, $height)
    {
        return $width . ' * ' . $height . ' px';
    }

    /**
     * Sends a video feedback to support along with description
     *
     * @param string $description the description
     *
     * @return boolean the result of sending
     */
    public function reportVideo($description)
    {
        global $UNI_CONTACT, $user;

        if (!\Config::get()->OPENCAST_ALLOW_TECHNICAL_FEEDBACK) {
            throw new \AccessDeniedException();
        }

        try {
            $opencast_support_email = \Config::get()->OPENCAST_SUPPORT_EMAIL;
            if (!filter_var($opencast_support_email, FILTER_VALIDATE_EMAIL)) {
                $opencast_support_email = $UNI_CONTACT;
            }
            $subject = '[Opencast] Feedback';
            $mailbody  = "Beschreibung:" . "\n";
            $mailbody .= $description . "\n\n";
            $mailbody .= "Grundinformationen:" . "\n";
            $mailbody .= sprintf("Video ID: %s", $this->id) . "\n";
            $mailbody .= sprintf("Opencast Episode ID: %s", $this->episode) . "\n";
            $mailbody .= sprintf("Opencast Server Config ID: %s", $this->config_id) . "\n";

            $feedback = new \StudipMail();

            $feedback->setSubject($subject)
                        ->addRecipient($opencast_support_email)
                        ->setBodyText($mailbody)
                        ->setSenderEmail($user->email)
                        ->setSenderName($user->getFullName())
                        ->setReplyToEmail($user->email);

            return $feedback->send();
        } catch (\Throwable $th) {
            throw new Error('Unable to send email', 500);
        }
        return false;
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
    public static function addToCoursePlaylist($eventType, $episode, $video)
    {
        // check if a series is assigned to this event
        if (!isset($episode->is_part_of) || empty($episode)) {
            return;
        }

        // get the courses this series belongs to
        $series = SeminarSeries::findBySeries_id($episode->is_part_of);
        foreach ($series as $s) {
            // Only add video to default playlist if it is not connected to a any playlist in this course
            $stmt = \DBManager::get()->prepare('SELECT count(*) FROM oc_playlist_seminar AS ops
                INNER JOIN oc_playlist_video AS opv ON (opv.playlist_id = ops.playlist_id AND opv.video_id = ?)
                WHERE ops.seminar_id = ?
            ');
            $stmt->execute([$video->id, $s['seminar_id']]);
            $count = intVal($stmt->fetchColumn());
            if ($count == 0) {
                $playlist = null;
                // Determine if the event is scheduled recordings.
                $scheduled_recording = ScheduledRecordings::findOneBySql('event_id = ? AND series_id = ? AND is_livestream = 0', [$video->episode, $episode->is_part_of]);
                if (!empty($scheduled_recording)) {
                    $seminar_playlist = PlaylistSeminars::findOneBySql('seminar_id = ? AND contains_scheduled = 1', [$s['seminar_id']]);
                    $playlist = !empty($seminar_playlist) ? $seminar_playlist->playlist : null;
                }

                if (empty($playlist)) {
                    // Add video to default playlist here
                    $playlist = Helpers::checkCoursePlaylist($s['seminar_id']);
                }

                $pvideo = PlaylistVideos::findOneBySQL('video_id = ? AND playlist_id = ?', [$video->id, $playlist->id]);

                if (empty($pvideo)) {
                    $pvideo = new PlaylistVideos();
                    $pvideo->video_id    = $video->id;
                    $pvideo->playlist_id = $playlist->id;
                    $pvideo->store();

                    // update playlist in opencast as well
                    if (PlaylistMigration::isConverted()) {
                        $playlist->addEntries([$video]);
                    }
                }
            }
        }
    }
}
