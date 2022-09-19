<?php

namespace Opencast\Models;

use Error;
use Opencast\Models\Filter;
use Opencast\Models\Tags;
use Opencast\Models\Playlists;
use Opencast\Models\REST\SearchClient;
use Opencast\Models\REST\ApiEventsClient;
use Opencast\Models\REST\ApiWorkflowsClient;

class Videos extends UPMap
{
    protected static function configure($config = [])
    {
        $config['db_table'] = 'oc_video';

        $config['has_many']['perms'] = [
            'class_name' => 'Opencast\\Models\\VideosUserPerms',
            'assoc_foreign_key' => 'video_id',
        ];

        $config['has_many']['video_seminars'] = [
            'class_name' => 'Opencast\\Models\\VideoSeminars',
            'assoc_foreign_key' => 'video_id',
        ];

        $config['has_and_belongs_to_many']['tags'] = [
            'class_name'     => 'Opencast\\Models\\Tags',
            'thru_table'     => 'oc_video_tags',
            'thru_key'       => 'video_id',
            'thru_assoc_key' => 'tag_id'
        ];

        parent::configure($config);
    }

    public static function findByFilter($filters)
    {

        global $user;

        $params = [
            ':user_id'=> $user->id
        ];

        $sql  = ' INNER JOIN oc_video_user_perms AS p ON (p.user_id = :user_id AND p.video_id = id) ';

        $where = ' WHERE 1 ';
        $tag_ids      = [];
        $playlist_ids = [];

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
                        $tags = Tags::findBySQL($sq = 'tag LIKE ?',  $pr = ['%'. $filter['value'] .'%']);

                        if (!empty($tags)) {
                            foreach ($tags as $tag) {
                                $tag_ids[] = $tag->id;
                            }
                        } else {
                            $tag_ids[] = '-1';
                        }
                    }
                    break;

                case 'playlist':
                    $playlists = Playlists::findByToken($filter['value']);

                    if (!empty($playlists)) {
                        foreach ($playlists as $playlist) {
                            $playlist_ids[] = $playlist->id;
                        }
                    } else {
                        $playlist_ids[] = '-1';
                    }

                    break;
            }
        }

        if (!empty($tag_ids)) {
            $sql .= ' INNER JOIN oc_video_tags AS t ON (t.tag_id IN('. implode(',', $tag_ids) .'))';
        }

        if (!empty($playlist_ids)) {
            $sql .= ' INNER JOIN oc_playlist_video AS opv ON (opv.playlist_id IN('. implode(',', $playlist_ids) .'))';
            $where .= ' AND opv.video_id = id';
        }

        if ($course_id = $filters->getCourseId()) {
            $sql .= ' INNER JOIN oc_video_seminar AS vs ON (vs.seminar_id = :seminar_id AND vs.video_id = id)';
            $params[':seminar_id'] = $course_id;
        }

        $sql .= $where;

        $sql .= ' GROUP BY oc_video.id';

        $stmt = \DBManager::get()->prepare($s = "SELECT COUNT(*) FROM (SELECT oc_video.* FROM oc_video $sql) t");
        $stmt->execute($params);
        $count = $stmt->fetchColumn();

        // TODO implement custom order
        [$field, $order] = explode("_", $filters->getOrder());

        if ($field === 'order') {
            if (!empty($playlist_ids)) {
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

    public static function findByToken($token)
    {
        return self::findOneBySQL('token = ?', [$token]);
    }

    public function toSanitizedArray()
    {
        $data = $this->toArray();

        $data['chdate'] = ($data['chdate'] == '0000-00-00 00:00:00')
            ? 0 : \strtotime($data['chdate']);

        $data['mkdate'] = ($data['mkdate'] == '0000-00-00 00:00:00')
            ? 0 : \strtotime($data['mkdate']);

        if ($data['episode']) {
            $search_client = SearchClient::getInstance($data['config_id']);
            $data['paella'] = $search_client->getBaseURL() . "/paella/ui/watch.html?id=" . $data['episode'];
        }

        $data['preview']     = json_decode($data['preview'], true);
        $data['publication'] = json_decode($data['publication'], true);

        $data['perm'] = $this->getUserPerm();
        $data['courses'] = $this->getCourses();

        $data['tags'] = $this->tags->toArray();

        return $data;
    }

    /**
     * Gets the list of assigned courses with additional course's infos
     *
     * @return string $courses the list of seminars
     */
    private function getCourses()
    {
        $courses = [];
        if (!empty($this->video_seminars)) {
            foreach ($this->video_seminars as $video_seminar) {
                $course = $video_seminar->course;
                $courses[] = [
                    'id' => $course->id,
                    'name' => $course->getFullname(),
                    'semester_name' => $course->getFullname('sem-duration-name')
                ];
            }
        }

        return $courses;
    }

    /**
     * Gets the perm value related to this video for the current user.
     *
     * @return string $perm the perm value
     */
    public function getUserPerm()
    {
        global $user;

        $ret_perm = 'read';

        foreach ($this->perms as $perm) {
            if ($perm->user_id == $user->id) {
                $ret_perm = $perm->perm;
            }
        }

        return $ret_perm;
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
        $allowed_metadata_fields = ['title', 'contributors', 'subject', 'language', 'description', 'startDate'];
        $metadata = [];
        foreach ($allowed_metadata_fields as $field_name) {
            if (isset($event[$field_name])) {
                $value = $event[$field_name];
                $id = $field_name;
                if ($field_name == 'subject') {
                    $id = 'subjects';
                    $value = [$value];
                }
                if ($field_name == 'contributors') {
                    $id = 'contributor';
                    $value = [$value];
                }

                $metadata[] = [
                    'id' => $id,
                    'value' => $value
                ];
            }
        }
        $success = false;
        $response = $api_event_client->updateMetadata($this->episode, $metadata);
        if ($response) {
            $api_wf_client = ApiWorkflowsClient::getInstance($this->config_id);
            if($api_wf_client->republish($this->episode)) {
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
        if ($api_event_client->deleteEpisode($this->episode)) {
            return $this->delete();
        }
        return false;
    }

    private static function filterForEpisode($episode_id, $acl)
    {
        $possible_roles = [
            'STUDIP_' . $episode_id . '_read',
            'STUDIP_' . $episode_id . '_write',
            'ROLE_ANONYMOUS'
        ];

        $result = [];
        foreach ($acl as $entry) {
            if (in_array($entry['role'], $possible_roles) !== false) {
                $result[] = $entry;
            }
        }

        return $result;
    }

    private static function addEpisodeAcl($episode_id, $add_acl, $acl)
    {
        $possible_roles = [
            'STUDIP_' . $episode_id . '_read',
            'STUDIP_' . $episode_id . '_write',
            'ROLE_ANONYMOUS'
        ];

        $result = [];
        foreach ($acl as $entry) {
            if (in_array($entry['role'], $possible_roles) === false) {
                $result[] = $entry;
            }
        }

        return array_merge($result, $add_acl);
    }

    /**
     * Check that the episode has its unique ACL and set it if necessary
     *
     * @Notification OpencastVideoSync
     *
     * @param string                $eventType
     * @param object                $episode
     * @param Opencast\Models\Video $video
     *
     * @return void
     */
    public static function checkEventACL($eventType, $episode, $video)
    {
        $api_client      = ApiEventsClient::getInstance($video->config_id);
        $workflow_client = ApiWorkflowsClient::getInstance($video->config_id);

        $current_acl = $api_client->getAcl($video->episode);

        // one ACL for reading AND for reading and writing
        $acl = [
            [
                'allow'  => true,
                'role'   => 'STUDIP_' . $video->episode .'_read',
                'action' => 'read'
            ],

            [
                'allow'  => true,
                'role'   => 'STUDIP_' . $video->episode .'_write',
                'action' => 'read'
            ],

            [
                'allow'  => true,
                'role'   => 'STUDIP_' . $video->episode .'_write',
                'action' => 'write'
            ]
        ];

        $oc_acl = self::filterForEpisode($video->episode, $current_acl);

        // add anonymous role if video is world visible
        if ($video->visibility == 'public') {
            $acl = [
                'allow'  => true,
                'role'   => 'ROLE_ANONYMOUS',
                'action' => 'read'
            ];
        }

        if ($acl <> $oc_acl) {
            $new_acl = self::addEpisodeAcl($video->episode, $acl, $oc_acl);
            $api_client->setACL($video->episode, $new_acl);
            $workflow_client->republish($video->episode);
        }
    }

    /**
     * Extract data from the OC event and adds it to the videos db entry
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
            $presentation_preview  = false;
            $preview               = false;
            $presenter_download    = [];
            $presentation_download = [];
            $audio_download        = [];
            $annotation_tool       = false;
            $duration              = 0;

            foreach ((array) $episode->publications[0]->attachments as $attachment) {
                if ($attachment->flavor === "presenter/search+preview" || $attachment->type === "presenter/search+preview") {
                    $preview = $attachment->url;
                }
                if ($attachment->flavor === "presentation/player+preview" || $attachment->type === "presentation/player+preview") {
                    $presentation_preview = $attachment->url;
                }
            }

            foreach ($episode->publications[0]->media as $track) {
                $parsed_url = parse_url($track->url);

                if ($track->flavor === 'presenter/delivery') {
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

                if ($track->flavor === 'presentation/delivery' && (
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
            }

            ksort($presenter_download);
            ksort($presentation_download);
            ksort($audio_download);

            $video->duration = $duration;

            $video->preview = json_encode([
                'search' => $preview,
                'player' => $presentation_preview
            ]);

            $video->publication = json_encode([
                'downloads' => [
                    'presenter'    => $presenter_download,
                    'presentation' => $presentation_download,
                    'audio'        => $audio_download
                ],
                'annotation_tool'  => $annotation_tool,
                'track_link'       => $track_link
            ]);

            $video->created = date('Y-m-d H:i:s', strtotime($episode->created));

            $video->author = $episode->creator;
            $video->contributors = implode(', ', $episode->contributor);

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
            throw new Error(_('Unable to send email'), 500);
        }
        return false;
    }
}
