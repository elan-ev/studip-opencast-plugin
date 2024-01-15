<?php

namespace Opencast\Models;

class Playlists extends UPMap
{
    protected static function configure($config = [])
    {
        $config['db_table'] = 'oc_playlist';

        $config['has_many']['perms'] = [
            'class_name' => 'Opencast\\Models\\PlaylistsUserPerms',
            'assoc_foreign_key' => 'playlist_id',
        ];

        $config['has_and_belongs_to_many']['tags'] = [
            'class_name'     => 'Opencast\\Models\\Tags',
            'thru_table'     => 'oc_playlist_tags',
            'thru_key'       => 'playlist_id',
            'thru_assoc_key' => 'tag_id'
        ];

        $config['has_many']['videos'] = [
            'class_name' => 'Opencast\\Models\\PlaylistVideos',
            'assoc_foreign_key' => 'playlist_id',
        ];

        $config['has_and_belongs_to_many']['courses'] = [
            'class_name'     => 'Course',
            'thru_table'     => 'oc_playlist_seminar',
            'thru_key'       => 'playlist_id',
            'thru_assoc_key' => 'seminar_id'
        ];

        parent::configure($config);
    }

    /**
     * Get the list of accessible course playlists, faceted by the passed filters
     *
     * @param String $course_id
     * @param Filter $filters
     * @param String|null $user_id
     * @return array
     *  [
     *      'playlists' => [Opencast\Models\PlaylistSeminars]
     *      'count'     => int
     *  ];
     */
    public static function getCoursePlaylists(String $course_id, Filter $filters, String $user_id = null)
    {
        // Check if user has access to playlist
        $sql    = " INNER JOIN oc_playlist ON (oc_playlist.id = oc_playlist_seminar.playlist_id)";
        $where  = " WHERE oc_playlist_seminar.seminar_id = :course_id ";
        $params = [
            ':course_id' => $course_id,
        ];

        return self::getFilteredPlaylists([
            'sql'    => $sql,
            'where'  => $where,
            'params' => $params,
        ], $filters);
    }

    /**
     * Get the list of accessible playlists, faceted by the passed filters
     *
     * @param Filter $filters
     * @param String|null $user_id
     * @return array
     *  [
     *      'playlists' => [Opencast\Models\PlaylistSeminars]
     *      'count'     => int
     *  ];
     */
    public static function getUserPlaylists(Filter $filters, String $user_id = null)
    {
        global $user;

        if (!$user_id) {
            $user_id = $user->id;
        }

        // Check if user has access to playlist
        $sql    = " INNER JOIN oc_playlist_user_perms AS ocp ON (ocp.playlist_id = oc_playlist.id)";
        $where  = " WHERE ocp.user_id = :user_id AND ocp.perm IN ('owner', 'write', 'read') ";
        $params = [
            ':user_id' => $user_id,
        ];

        return self::getFilteredPlaylists([
            'sql'    => $sql,
            'where'  => $where,
            'params' => $params,
        ], $filters);
    }


    /**
     * Add filters to the passed data and return playlists.
     *
     * @param array $query
     *    [
     *        'sql'   => '',
     *        'where' => '',
     *        'params' => ''
     *    ];
     * @param Filter $filters
     *
     * @return array
     *    [
     *        'videos' => [Opencast\Models\Playlist]|[Opencast\Models\PlaylistSeminars],
     *        'sql'    => '...,
     *        'count'  => int
     *    ];
     */
    private static function getFilteredPlaylists(array $query, Filter $filters)
    {
        global $perm;

        $sql    = $query['sql'];
        $where  = $query['where'];
        $params = $query['params'];

        $tags      = [];
        $courses   = [];
        $lecturers = [];

        // Apply filters
        foreach ($filters->getFilters() as $filter) {
            switch ($filter['type']) {
                case 'text':
                    $pname = ':text' . sizeof($params);
                    $where .= " AND (title LIKE $pname)";
                    $params[$pname] = '%' . $filter['value'] .'%';
                    break;

                case 'tag':
                    // get id of this tag (if any)
                    if (!empty($filter['value'])) {
                        $tags_obj = Tags::findBySQL($sq = 'tag LIKE ?',  [$filter['value']]);

                        if (!empty($tags_obj)) {
                            $tags[$filter['value']] = [
                                'tag_ids' => array_map(function ($tag) {
                                    return $tag->id;
                                }, $tags_obj),
                                'compare' => $filter['compare'],
                            ];
                        }
                    }
                    break;

                case 'course':
                    $course = \Course::find($filter['value']);

                    // check, if user has access to this seminar
                    if (!empty($course) && $perm->have_studip_perm($course->id, 'user')) {
                        $courses[$course->id] = [
                            'id' => $course->id,
                            'compare' => $filter['compare']
                        ];
                    }
                    break;

                case 'lecturer':
                    $lecturer = \User::findByUsername($filter['value']);

                    if (!empty($lecturer)) {
                        $lecturers[$lecturer->user_id] = [
                            'id' => $lecturer->user_id,
                            'compare' => $filter['compare']
                        ];
                    }
                    break;
            }
        }


        if (!empty($tags)) {
            foreach ($tags as $value => $tag_filter) {
                $tags_param = ':tags' . $value;
                $params[$tags_param] = $tag_filter['tag_ids'];
                if ($tag_filter['compare'] == '=') {
                    $sql .= ' INNER JOIN oc_playlist_tags AS t'. $value .' ON (t'. $value .'.playlist_id = oc_playlist.id '
                        .' AND t'. $value .'.tag_id IN ('. $tags_param .'))';
                } else {
                    $sql .= ' LEFT JOIN oc_playlist_tags AS t'. $value .' ON (t'. $value .'.playlist_id = oc_playlist.id '
                        .' AND t'. $value .'.tag_id IN ('. $tags_param .'))';

                    $where .= ' AND t'. $value . '.tag_id IS NULL ';
                }
            }
        }

        if (!empty($courses)) {
            foreach ($courses as $course) {
                if ($course['compare'] == '=') {
                    $sql .= " INNER JOIN oc_playlist_seminar AS ops". $course['id'] ." ON "
                        ." (ops". $course['id'] .".playlist_id = oc_playlist.id "
                        ." AND ops". $course['id'] .".seminar_id = '". $course['id'] ."')";
                } else {
                    $where .= " AND '". $course['id'] ."' NOT IN ("
                        ." SELECT DISTINCT ops". $course['id'].".seminar_id FROM oc_playlist_seminar AS ops". $course['id']
                        ." WHERE ops". $course['id'].".playlist_id = oc_playlist.id)";
                }
            }
        }

        if (!empty($lecturers)) {
            foreach ($lecturers as $lecturer) {
                if ($lecturer['compare'] == '=') {
                    $sql .= " INNER JOIN oc_playlist_seminar AS ops". $lecturer['id'] ." ON "
                        ." (ops". $lecturer['id'] .".playlist_id = oc_playlist.id)";
                    $where .= " AND ops". $lecturer['id'] .".seminar_id IN ("
                        ." SELECT DISTINCT su". $lecturer['id'] .".Seminar_id FROM seminar_user AS su". $lecturer['id']
                        ." WHERE su". $lecturer['id'] .".user_id = '". $lecturer['id']. "' AND su". $lecturer['id'] .".status = 'dozent')";
                } else {
                    $where .= " AND oc_playlist.id NOT IN (SELECT DISTINCT ops". $lecturer['id'] .".playlist_id FROM oc_playlist_seminar AS ops". $lecturer['id']
                        ." INNER JOIN seminar_user AS su". $lecturer['id'] ." ON (su". $lecturer['id'] .".Seminar_id = ops". $lecturer['id'] .".seminar_id)"
                        ." WHERE su". $lecturer['id'] .".user_id = '". $lecturer['id'] ."' AND su". $lecturer['id'] .".status = 'dozent')";
                }
            }
        }


        $sql .= $where;

        $sql .= ' GROUP BY oc_playlist.id';

        // Count playlists
        if (!$filters->getCourseId()) {
            $count_sql = "SELECT COUNT(*) FROM (SELECT oc_playlist.* FROM oc_playlist $sql) t";
        } else {
            $count_sql = "SELECT COUNT(*) FROM (SELECT oc_playlist_seminar.* FROM oc_playlist_seminar $sql) t";
        }

        $stmt = \DBManager::get()->prepare($count_sql);
        $stmt->execute($params);
        $count = $stmt->fetchColumn();

        if ($filters->getCourseId()) {
            $sql .= ' ORDER BY oc_playlist_seminar.is_default DESC';
        }

        if ($filters->getLimit() != -1) {
            $sql .= ' LIMIT '. $filters->getOffset() . ', '. $filters->getLimit();
        }

        $parsed_sql = 'SELECT * FROM oc_playlist '. $sql;
        foreach ($params as $key => $value) {
            $parsed_sql = str_replace($key, "'". $value ."'", $parsed_sql);
        }

        return [
            'playlists' => !$filters->getCourseId() ? self::findBySQL($sql, $params): PlaylistSeminars::findBySQL($sql, $params),
            'sql'    => $parsed_sql,
            'count'  => $count
        ];
    }

    public static function findByCourse_id($course_id)
    {
        return self::findBySQL('LEFT JOIN oc_playlist_seminar AS ocps
            ON (ocps.playlist_id = oc_playlist.id)
            WHERE ocps.seminar_id = ?', [$course_id]);
    }


    /**
     * Gets the perm value related to this video for the current user.
     *
     * @return string $perm the perm value
     */
    public function getUserPerm()
    {
        global $user, $perm;;

        $ret_perm = false;

        if ($perm->have_perm('root', $user->id)) {
            return 'owner';
        }

        foreach ($this->perms as $uperm) {
            if ($uperm->user_id == $user->id) {
                $ret_perm = $uperm->perm;
            }
        }

        if (!$ret_perm) {
            // check if user has read access through a linked course
            foreach ($this->courses as $course) {
                if ($course->getParticipantStatus($user->id)) {
                    return 'read';
                }
            }
        }

        return $ret_perm;
    }

    /**
     * Check if current user has permission for a course of this playlist
     */
    public function haveCoursePerm(String $perm) {
        foreach ($this->courses as $course) {
            if ($GLOBALS['perm']->have_studip_perm($perm, $course->id)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get sanitized array to send to the frontend
     */
    public function toSanitizedArray()
    {
        $data = $this->toArray();
        unset($data['id']);
        $data['videos_count'] = count($this->videos);

        $data['tags'] = $this->tags->toArray();

        if (!is_null($data['allow_download'])) {
            $data['allow_download'] = filter_var(
                $data['allow_download'],
                FILTER_VALIDATE_BOOLEAN
            );
        }

        return $data;
    }

    public function copy()
    {
        global $user;

        $new_playlist = self::create([
            'title'          => $this->title,
            'visibility'     => $this->visibility,
            'sort_order'     => $this->sort_order,
            'allow_download' => $this->allow_download,
        ]);

        // Set current user as owner for this playlist
        PlaylistsUserPerms::create([
            'playlist_id' => $new_playlist->id,
            'user_id'     => $user->id,
            'perm'        => 'owner'
        ]);

        // Link videos to new playlist
        foreach ($this->videos as $video) {
            PlaylistVideos::create([
                'playlist_id' => $new_playlist->id,
                'video_id' => $video->video_id,
            ]);
        }

        // Copy tags
        foreach ($this->tags as $tag) {
            $tag->copy($new_playlist->id);
        }

        $new_playlist->store();

        return $new_playlist;
    }

    public function store()
    {
        if (!$this->token) {
            $this->token = bin2hex(random_bytes(8));
        }

        return parent::store();
    }
}
