<?php

namespace Opencast\Models;

use Opencast\Models\REST\ApiPlaylistsClient;

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

        $filtered_query = self::getFilteredPlaylistsQuery([
            'sql'    => $sql,
            'where'  => $where,
            'params' => $params,
        ], $filters);

        $filtered_sql = $filtered_query['sql'];
        $filtered_params = $filtered_query['params'];

        // Count playlists
        $stmt = \DBManager::get()->prepare("SELECT COUNT(*) FROM (SELECT oc_playlist_seminar.* FROM oc_playlist_seminar $filtered_sql) t");
        $stmt->execute($filtered_params);
        $count = $stmt->fetchColumn();

        if ($filters->getLimit() != -1) {
            $filtered_sql .= ' LIMIT '. $filters->getOffset() . ', '. $filters->getLimit();
        }

        return [
            'playlists' => PlaylistSeminars::findBySQL($filtered_sql, $filtered_params),
            'count'  => $count
        ];
    }

    /**
     * Get the list of accessible playlists, faceted by the passed filters
     *
     * @param Filter $filters
     * @param String|null $user_id
     * @return array
     *  [
     *      'playlists' => [Opencast\Models\Playlists]
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

        $filtered_query = self::getFilteredPlaylistsQuery([
            'sql'    => $sql,
            'where'  => $where,
            'params' => $params,
        ], $filters);

        $filtered_sql = $filtered_query['sql'];
        $filtered_params = $filtered_query['params'];

        // Count playlists
        $stmt = \DBManager::get()->prepare("SELECT COUNT(*) FROM (SELECT oc_playlist.* FROM oc_playlist $filtered_sql) t");
        $stmt->execute($filtered_params);
        $count = $stmt->fetchColumn();

        if ($filters->getLimit() != -1) {
            $filtered_sql .= ' LIMIT '. $filters->getOffset() . ', '. $filters->getLimit();
        }

        return [
            'playlists' => self::findBySQL($filtered_sql, $filtered_params),
            'count'  => $count
        ];
    }


    /**
     * Add filters to the passed data and return query with filters.
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
     *        'sql'    => '',
     *        'params'  => ''
     *    ];
     */
    private static function getFilteredPlaylistsQuery(array $query, Filter $filters)
    {
        global $perm;

        $sql    = $query['sql'];
        $where  = $query['where'];
        $params = $query['params'];

        $tags      = [];
        $courses   = [];
        $lecturers = [];
        $orderable_columns = [
            'mkdate', 'chdate', 'id', 'title', 'presenters'
        ];

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

        if ($order = $filters->getOrder()) {
            list($column, $direction) = explode('_', $order, 2);
            if (!empty($column) && !empty($direction) && in_array($column, $orderable_columns, true)) {
                $sql .= " ORDER BY oc_playlist.{$column} {$direction}";
            }
        }

        return [
            'sql'    => $sql,
            'params' => $params
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
     * Get default ACLs array for a playlist
     *
     * @param string $playlistId playlist identifier. If null, only user role will be added.
     * @return array access control list
     */
    public static function getDefaultACL($playlistId = null)
    {
        global $user;

        $acls = [
            [
                'allow'  => true,
                'role'   => "STUDIP_$user->user_id", // TODO: For videos we use the user role from /info/me.js. Here we have no LTI user.
                'action' => 'read'
            ],
            [
                'allow'  => true,
                'role'   => "STUDIP_$user->user_id",
                'action' => 'write'
            ],
        ];

        if (!is_null($playlistId)) {
            array_push($acls,
                [
                    'allow'  => true,
                    'role'   => "STUDIP_PLAYLIST_{$playlistId}_read",
                    'action' => 'read'
                ],
                [
                    'allow'  => true,
                    'role'   => "STUDIP_PLAYLIST_{$playlistId}_write",
                    'action' => 'read'
                ],
                [
                    'allow'  => true,
                    'role'   => "STUDIP_PLAYLIST_{$playlistId}_write",
                    'action' => 'write'
                ]
            );
        }

        return $acls;
    }

    /**
     * Update playlist in Opencast and DB
     *
     * @param array $json playlist data
     * @return boolean update successful
     */
    public function update(array $json)
    {
        $playlist_client = ApiPlaylistsClient::getInstance($this->config_id);

        // Load playlist from Opencast
        $oc_playlist = $playlist_client->getPlaylist($this->service_playlist_id);

        if ($oc_playlist) {
            $acls = $oc_playlist->accessControlEntries;
            if (empty($acls)) {
                $acls = self::getDefaultACL($oc_playlist->id);
            }

            // TODO: Why is it necessary to remove the id?
            array_walk($acls, function ($acl) {
               unset($acl->id);
            });

            // Update playlist in Opencast
            $oc_playlist = $playlist_client->updatePlaylist($oc_playlist->id, [
                'title' => $json['title'],
                'description' => $json['description'],
                'creator' => $json['creator'],
                'entries' => $oc_playlist->entries,
                'accessControlEntries' => $acls
            ]);
        }

        if (!$oc_playlist) {
            // Load or update failed in Opencast
            return false;
        }

        // Ensure playlist data is consistent
        $json['title'] = $oc_playlist->title;
        $json['description'] = $oc_playlist->description;
        $json['creator'] = $oc_playlist->creator;
        $json['updated'] = date('Y-m-d H:i:s', strtotime($oc_playlist->updated));

        $this->setData($json);
        $this->setEntries($oc_playlist->entries);
        $this->store();

        return true;
    }

    /**
     * Synchronize this playlist with Opencast playlist
     *
     * @return boolean Successfully synchronized with opencast playlist
     */
    public function synchronize()
    {
        $playlist_client = ApiPlaylistsClient::getInstance($this->config_id);

        $oc_playlist = $playlist_client->getPlaylist($this->service_playlist_id);

        if (!$oc_playlist) {
            return false;
        }

        // Update playlist
        $this->title = $oc_playlist->title;
        $this->description = $oc_playlist->description;
        $this->creator = $oc_playlist->creator;
        $this->updated = date('Y-m-d H:i:s', strtotime($oc_playlist->updated));

        $this->setEntries($oc_playlist->entries);

        $this->store();

        return true;
    }

    /**
     * Set playlist videos in playlist based on passed entries
     *
     * @param array $entries Opencast playlist entries
     */
    public function setEntries(Array $entries)
    {
        $playlist_videos = PlaylistVideos::findBySql(
            'playlist_id = ?', [$this->id]
        );

        // Iterate over existing playlist videos to be removed
        foreach ($playlist_videos as $playlist_video) {
            $db_video = Videos::find($playlist_video->video_id);

            // Check if video already exists in playlist
            $existing_entry = null;
            foreach ($entries as $entry) {
                if ($entry->contentId === $db_video->episode) {
                    $existing_entry = $entry;
                    break;
                }
            }

            // Remove video from playlist if not exist in opencast playlist entries
            if (is_null($existing_entry)) {
                // TODO: Should we check video permission?
                //if (in_array($db_video->getUserPerm(), ['owner', 'write']) === true) {
                    $playlist_video->delete();
                //}
            }
        }

        // Add new entries
        foreach ($entries as $key => $entry) {
            $db_video = Videos::findByEpisode($entry->contentId);

            if (is_null($db_video)) {
                // Create dummy video without permissions for videos not available yet or removed from Stud.IP
                $db_video = new Videos;
                $db_video->setData([
                    'episode'      => $entry->contentId,
                    'config_id'    => $this->config_id,
                    'created'      => date('Y-m-d H:i:s'),
                    'available'    => false
                ]);
                if (!$db_video->token) {
                    $db_video->token = bin2hex(random_bytes(8));
                }
                $db_video->store();
            }

            $playlist_video = PlaylistVideos::findOneBySQL('video_id = ? AND playlist_id = ?', [$db_video->id, $this->id]);

            if (is_null($playlist_video)) {
                // check if user has perms on the video
                // TODO: Should we check video permission?
                //if (in_array($db_video->getUserPerm(), ['owner', 'write']) === true) {
                    $playlist_video = PlaylistVideos::create([
                        'video_id' => $db_video->id,
                        'playlist_id' => $this->id,
                        'service_entry_id' => $entry->id,
                    ]);
               // }
            }

            if (!is_null($playlist_video)) {
                // Always update entry id and order
                $playlist_video->service_entry_id = $entry->id;
                $playlist_video->order = $key;
                $playlist_video->store();
            }
        }
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

        $data['courses'] = [];
        if (!empty($this->courses)) {
            $data['courses'] = PlaylistSeminars::getCoursesArray(
                $this->courses->map(function ($course) {
                    return $course->id;
                })
            );
        }

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
                'video_id'    => $video->video_id,
                'order'       => $video->order,
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
