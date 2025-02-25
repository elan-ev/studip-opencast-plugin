<?php

namespace Opencast\Models;

use Opencast\Models\REST\ApiPlaylistsClient;
use Opencast\Helpers\PlaylistMigration;
use Opencast\Errors\Error;

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
     * Returns the list of playlists that are accessible by the user via the user's courses.
     *
     * @param string $user_id
     *
     * @return array the playlist ids
     */
    public static function getUserCoursesPlaylistIDs($user_id)
    {
        // get all courses this user has access to. Only courses with activated OC plugin are included
        $courses = Helpers::getMyCourses($user_id);

        if (empty($courses)) {
            return [];
        }

        $stmt = \DBManager::get()->prepare($sql = 'SELECT playlist_id FROM oc_playlist_seminar
            WHERE seminar_id IN (:courses)
        ');

        $stmt->bindValue(':courses', $courses, \StudipPDO::PARAM_ARRAY);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
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

        // Check if user has access to playlist via explicit perms or user's courses
        $sql    = " LEFT JOIN oc_playlist_user_perms AS ocp ON (ocp.playlist_id = oc_playlist.id)";
        $where  = " WHERE (oc_playlist.id IN (:playlist_ids) OR ocp.user_id = :user_id AND ocp.perm IN ('owner', 'write', 'read')) ";
        $params = [
            ':user_id' => $user_id,
            ':playlist_ids' => self::getUserCoursesPlaylistIDs($user_id),
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
                    if (!empty($course) && $perm->have_studip_perm('user', $course->id)) {
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
            // check if user is lecturer and therefore has owner rights or at least
            // has read access through a linked course
            foreach ($this->courses as $course) {
                if ($perm->have_studip_perm('dozent', $course->id)) {
                    return 'owner';
                }

                if ($course->getParticipantStatus($user->id)) {
                    $ret_perm = 'read';
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
     * Get default ACLs array for a playlist containing user and playlist roles
     *
     * @param string $playlistId playlist identifier. If null, only user role will be added.
     * @return array access control list
     */
    public static function getDefaultACL($playlistId, $user_id = null)
    {
        return [
            [
                'allow'  => true,
                'role'   => "PLAYLIST_{$playlistId}_read",
                'action' => 'read'
            ],
            [
                'allow'  => true,
                'role'   => "PLAYLIST_{$playlistId}_write",
                'action' => 'read'
            ],
            [
                'allow'  => true,
                'role'   => "PLAYLIST_{$playlistId}_write",
                'action' => 'write'
            ]
        ];
    }

    /**
     * Check that the playlist has its unique ACL and set it if necessary
     *
     * @param $oc_playlist object
     * @param $playlist Playlists
     * @return void
     */
    public static function checkPlaylistACL($oc_playlist, $playlist)
    {
        $old_acl = json_decode(json_encode($oc_playlist->accessControlEntries), true);
        // Remove the ACL IDs
        array_walk($old_acl, function (&$entry) {
            unset($entry['id']);
        });

        $acl = self::getDefaultACL($oc_playlist->id);

        // add user acls
        foreach ($playlist->courses as $course) {
            foreach($course->getMembersWithStatus('dozent') as $member) {
                $acl[] = [
                    'allow'  => true,
                    'role'   => $member->user_id .'_Instructor',
                    'action' => 'read'
                ];
                $acl[] = [
                    'allow'  => true,
                    'role'   => $member->user_id .'_Instructor',
                    'action' => 'write'
                ];
            }
        }

        $courses = [];
        $courses = array_merge($courses, $playlist->courses->pluck('id'));

        $acl = array_merge($acl, Helpers::createACLsForCourses($courses));

        $old_acls = Helpers::filterACLs($old_acl, $acl);

        // Reindex keys
        $current_acl = array_values($old_acls['studip']);

        sort($current_acl);
        sort($acl);

        if ($acl <> $current_acl) {
            // add the unknown acls to keep them untouched
            $new_acl = array_merge($old_acls['other'], $acl);

            $api_client = ApiPlaylistsClient::getInstance($playlist->config_id);
            $api_client->updatePlaylist($oc_playlist->id, [
                'title'                => $oc_playlist->title,
                'description'          => $oc_playlist->description,
                'creator'              => $oc_playlist->creator,
                'entries'              => $oc_playlist->entries,
                'accessControlEntries' => $new_acl
            ]);
        }
    }

    /**
     * Create playlist in Opencast and DB
     *
     * @param array $json playlist data
     * @param array $entries playlist entries
     *
     * @return Playlists|null created playlist
     */
    public static function createPlaylist($json, $entries = [])
    {

        if (PlaylistMigration::isConverted()) {
            $playlist_client = ApiPlaylistsClient::getInstance($json['config_id']);

            // Create playlist in Opencast
            $oc_playlist = $playlist_client->createPlaylist([
                'title'                => $json['title'],
                'description'          => $json['description'],
                'creator'              => $json['creator'],
                'entries'              => $entries,
                'accessControlEntries' => []
            ]);

            if (!$oc_playlist) {
                return null;
            }

            $json['service_playlist_id'] = $oc_playlist->id;
            $json['title']               = $oc_playlist->title;
            $json['description']         = $oc_playlist->description;
            $json['creator']             = $oc_playlist->creator;
            $json['updated']             = date('Y-m-d H:i:s', strtotime($oc_playlist->updated));

            $entries = $oc_playlist->entries;

            // load playlist from DB, if present
            $playlist = self::findOneBySQL('config_id = ? AND service_playlist_id = ?', [$json['config_id'], $oc_playlist->id]);
        } else {
            // convert the entries to an array of objects, otherwise setEntries will complain
            $entries = json_decode(json_encode($entries));
        }

        if (empty($playlist)) {
            $playlist = new Playlists;
        }

        $playlist->setData($json);
        $playlist->store();

        $playlist->setEntries($entries);

        if (PlaylistMigration::isConverted()) {
            self::checkPlaylistACL($oc_playlist, $playlist);
        }

        return $playlist;
    }

    /**
     * Update playlist in Opencast and DB
     *
     * @param array $json playlist data
     * @return boolean update successful
     */
    public function update(array $json)
    {
        // Only update in opencast if necessary
        if (PlaylistMigration::isConverted()
            && (isset($json['title']) || isset($json['description']) || isset($json['creator']))
        ) {
            // Load playlist from Opencast
            $playlist_client = ApiPlaylistsClient::getInstance($this->config_id);

            // Update playlist in Opencast
            $oc_update_data = [];
            foreach (['title', 'description', 'creator'] as $key) {
                if (array_key_exists($key, $json)) {
                    $oc_update_data[$key] = $json[$key];
                }
            }

            $oc_playlist = $playlist_client->updatePlaylist($this->service_playlist_id, $oc_update_data);

            if (!$oc_playlist) {
                // Update failed in Opencast
                return false;
            }

            // Ensure playlist acls are correct
            self::checkPlaylistACL($oc_playlist, $this);

            // Ensure playlist data is consistent
            $json['title']       = $oc_playlist->title;
            $json['description'] = $oc_playlist->description;
            $json['creator']     = $oc_playlist->creator;
            $json['updated']     = date('Y-m-d H:i:s', strtotime($oc_playlist->updated));

            $this->setEntries($oc_playlist->entries);
        }

        // Update in DB
        $this->setData($json);
        $this->store();

        return true;
    }

    /**
     * Delete playlist from Opencast and DB
     */
    public function delete()
    {
        if ($this->service_playlist_id) {
            // Delete from Opencast
            $playlist_client = ApiPlaylistsClient::getInstance($this->config_id);
            $playlist_client->deletePlaylist($this->service_playlist_id);
        }

        return parent::delete();
    }

    public function addEntries(Array $videos)
    {
        $playlist_client = ApiPlaylistsClient::getInstance($this->config_id);
        $oc_playlist = $playlist_client->getPlaylist($this->service_playlist_id);

        if (!$oc_playlist) {
            // something went wrong with playlist creation, try again
            $oc_playlist = $playlist_client->createPlaylist([
                'title'                => $this->title,
                'description'          => $this->description,
                'creator'              => $this->creator,
                'accessControlEntries' => []
            ]);

            if (!$oc_playlist) {
                throw new Error(_('Wiedergabeliste konnte nicht zu Opencast hinzugefügt werden!'), 500);
            }

            $this->service_playlist_id = $oc_playlist->id;
            $this->store();
        }

        $entries = $oc_playlist->entries;

        foreach ($videos as $video) {
            if (!$video->episode) continue;

            // Only add video if not contained in entries
            $entry_exists = current(array_filter($entries, function($e) use ($video) {
                return $e->contentId === $video->episode;
            }));

            if (!$entry_exists) {
                $entries[] = (object) [
                    'contentId' => $video->episode,
                    'type' => 'EVENT'
                ];
            }
        }

        // Update videos in playlist of Opencast
        $oc_playlist = $playlist_client->updateEntries($oc_playlist->id, $entries);
        if (!$oc_playlist) {
            throw new Error(_('Die Videos konnten nicht hinzugefügt werden.'), 500);
        }

        // Update playlist videos in DB
        $this->setEntries($oc_playlist->entries);
    }

    public function removeEntries(Array $videos)
    {
        // Get playlist entries from Opencast
        $playlist_client = ApiPlaylistsClient::getInstance($this->config_id);
        $oc_playlist = $playlist_client->getPlaylist($this->service_playlist_id);

        $old_entries = (array)$oc_playlist->entries;
        $entries = (array)$oc_playlist->entries;

        foreach ($videos as $video) {

            // Prevent removing video from playlist when it is livestream.
            if ((bool) $video->is_livestream) {
                continue;
                // return $this->createResponse([
                //     'message' => [
                //         'type' => 'error',
                //         'text' => _('Entfernung eines Livestream-Videos aus der Wiedergabeliste ist nicht erlaubt.')
                //     ],
                // ], $response->withStatus(403));
            }

            // Remove all occurrences of video from entries
            $entries = array_values(array_filter($entries, function ($entry) use ($video) {
                return $entry->contentId !== $video->episode;
            }));
        }

        if (count($entries) < count($old_entries)) {
            // Remove videos in playlist of Opencast
            $oc_playlist = $playlist_client->updateEntries($oc_playlist->id, $entries);
            if (!$oc_playlist) {
                throw new Error(_('Die Videos konnten nicht entfernt werden.'), 500);
            }
        }

        // Update playlist videos in DB
        $this->setEntries((array)$oc_playlist->entries);
    }

    /**
     * Set playlist videos in playlist based on passed entries.
     * This function checks no permissions.
     * IMPORTANT: This functions expects the entries to be an array of objects!
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

            // Remove video from playlist if not exists in opencast playlist entries
            if (is_null($existing_entry)) {
                $playlist_video->delete();
                Videos::checkEventACL(null, null, $db_video);
            }
        }

        // Create and update entries
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
                $playlist_video = PlaylistVideos::create([
                    'video_id'          => $db_video->id,
                    'playlist_id'       => $this->id,
                    'service_entry_id'  => $entry->id ?? null,
                    'available'         => false                      // this video is not available in courses yet
                ]);

                Videos::checkEventACL(null, null, $db_video);
            }

            if (!is_null($playlist_video)) {
                // Always update entry id and order
                $playlist_video->service_entry_id = $entry->id ?? null;
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

        $data['mkdate'] = ($this->mkdate == '0000-00-00 00:00:00')
            ? 0 : \strtotime($this->mkdate);

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

    /**
     * Copy playlist in Opencast and DB
     *
     * @return Playlists|null Copied playlist. Null if copy fails.
     */
    public function copy()
    {
        global $user;

        // Collect playlist videos
        $stmt = \DBManager::get()->prepare("SELECT oc_video.episode FROM oc_video
            INNER JOIN oc_playlist_video ON (oc_playlist_video.video_id = oc_video.id
                AND oc_playlist_video.playlist_id = ?)
            ORDER BY oc_playlist_video.order");
        $stmt->execute([$this->id]);
        $playlist_events = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        $entries = [];
        foreach ($playlist_events as $event) {
            $entries[] = [
                'contentId' => $event,
                'type' => 'EVENT'
            ];
        }

        $playlist_data = [
            'config_id'      => $this->config_id,
            'title'          => $this->title,
            'description'    => $this->description,
            'creator'        => $this->creator,
            'visibility'     => $this->visibility,
            'sort_order'     => $this->sort_order,
            'allow_download' => $this->allow_download
        ];

        // Create copy of playlist in Opencast and DB
        $new_playlist = self::createPlaylist($playlist_data, $entries);

        if (!$new_playlist) {
            return null;
        }

        // Set current user as owner for this playlist
        PlaylistsUserPerms::create([
            'playlist_id' => $new_playlist->id,
            'user_id'     => $user->id,
            'perm'        => 'owner'
        ]);

        // Copy tags
        foreach ($this->tags as $tag) {
            $tag->copy($new_playlist->id);
        }

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
