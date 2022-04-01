<?php

use Opencast\Models\OCConfig;
use Opencast\Models\Pager;
use Opencast\Models\OCSeminarEpisodes;
use Opencast\Configuration;

class ApiEventsClient extends OCRestClient
{
    public static $me;
    public        $serviceName = 'ApiEvents';

    public function __construct($config_id = 1)
    {
        if ($config = OCConfig::getConfigForService('apievents', $config_id)) {
            parent::__construct($config);
        } else {
            throw new Exception(_('Die Konfiguration wurde nicht korrekt angegeben'));
        }
    }

    /**
     * [getEpisode description]
     * @param  [type] $episode_id [description]
     * @return [type]             [description]
     */
    public function getEpisode($episode_id, $with_publications = false)
    {
        list($data, $code) = $this->getJSON('/' . $episode_id . '?withpublications=' . json_encode($with_publications), [], true, true);

        return [$code, $data];
    }

    public function getACL($episode_id)
    {
        return json_decode(json_encode($this->getJSON('/' . $episode_id . '/acl')), true);
    }

    public function setACL($episode_id, $acl)
    {
        $data = [
            'acl' => json_encode($acl->toArray())
        ];

        $result = $this->putJSON('/' . $episode_id . '/acl', $data, true);

        return $result[1] == 200;
    }

    /**
     *  Retrieves episode metadata for a given series identifier
     *  from connected Opencast
     *
     * @param string series_id Identifier for a Series
     *
     * @return array response of episodes
     */
    public function getBySeries($series_id, $course_id)
    {
        $events = [];

        $offset = Pager::getOffset();
        $limit  = Pager::getLimit();
        $sort   = Pager::getSortOrder();
        $search = Pager::getSearch();

        //get
        $series = $this->getJSON('/?filter=' . urlencode('is_part_of:' . $series_id));

        //check for new epsiodes
        $episodes_404 = [];
        foreach ($series as $episode) {
            $entry = OCSeminarEpisodes::findOneBySQL(
                'episode_id = ? AND series_id = ? AND seminar_id = ?',
                [$episode->identifier, $series_id, $course_id]
            );
            if (!$entry) {
                $episodes_404[] = $episode;
            }
        }
        //get new epsiodes
        $this->getEpisodes($episodes_404);

        //check_perms
        if (!OCPerm::editAllowed($course_id)) {
            $episodes = [];
            foreach ($series as $episode) {
                $entry = OCSeminarEpisodes::findOneBySQL(
                    'episode_id = ? AND series_id = ? AND seminar_id = ?',
                    [$episode->identifier, $series_id, $course_id]
                );
                if ($entry && $entry->visible != 'invisible') {
                    $episodes[] = $episode;
                }
            }
            $series = $episodes;
        }

        //skip upcoming livestream
        $config = OCConfig::getConfigForCourse($course_id);
        if (Configuration::instance($config['id'])->get('livestream')) {
            $now = time();
            foreach ($series as $episode) {
                $startTime = strtotime($episode['start']);
                $endTime = strtotime($episode['start']) + $episode['duration'] / 1000;
                $live = $now < $endTime;

                    /* today and the next full 7 days */;
                $isUpcoming = $startTime <= (strtotime("tomorrow") + 7 * 24 * 60 * 60);
                if ($live && !$isUpcoming) {
                    continue;
                }
                $episodes[] = $episode;
            }
        }

        // search
        if ($search) {
            $episodes = [];
            foreach ($series as $episode) {
                if (stripos($episode->title, $search) !== false) {
                    $episodes[] = $episode;
                }
            }
            $series = $episodes;
        }

        // sort
        if (!empty($series)) {
            switch ($sort) {
                case 'DATE_CREATED_DESC':
                    $columns_tmp = array_column($series, 'created');
                    $columns = [];
                    foreach ($columns_tmp as $col) {
                        $columns[] = strtotime($col);
                    }
                    array_multisort($columns, SORT_DESC, $series);
                    break;
                case 'DATE_CREATED':
                    $columns_tmp = array_column($series, 'created');
                    $columns = [];
                    foreach ($columns_tmp as $col) {
                        $columns[] = strtotime($col);
                    }
                    array_multisort($columns, SORT_ASC, $series);
                    break;
                case 'TITLE':
                    $columns = array_column($series, 'title');
                    array_multisort($columns, SORT_ASC, $series);
                    break;
                case 'TITLE_DESC':
                    $columns = array_column($series, 'title');
                    array_multisort($columns, SORT_DESC, $series);
                    break;
            }
        }

        $length = count($series);
        Pager::setLength($length);

        if ($length) {
            $events = array_slice($series, $offset, $limit);
        }

        // then, iterate over list and get each event from the external-api
        $events = $this->getEpisodes($events);

        return $events;
    }

    private function getEpisodes($episodes)
    {
        if (empty($episodes)) {
            return [];
        }
        $cache = \StudipCacheFactory::getCache();
        $events = [];
        foreach ($episodes as $s_event) {
            $cache_key = 'sop/episodes/' . $s_event->identifier;
            $event = $cache->read($cache_key);

            if (empty($s_event->identifier)) {
                continue;
            }

            if (!$event) {
                $oc_event = $this->getJSON('/' . $s_event->identifier . '/?withpublications=true');

                if ($oc_event) {
                    if (empty($oc_event->publications[0]->attachments)) {
                        $media = [];

                        foreach ($s_event->mediapackage->media->track as $track) {
                            $width = 0;
                            $height = 0;
                            if (!empty($track->video)) {
                                list($width, $height) = explode('x', $track->video->resolution);
                                $bitrate = $track->video->bitrate;
                            } else if (!empty($track->audio)) {
                                $bitrate = $track->audio->bitrate;
                            }

                            $obj = new stdClass();
                            $obj->mediatype = $track->mimetype;
                            $obj->flavor    = $track->type;
                            $obj->has_video = !empty($track->video);
                            $obj->has_audio = !empty($track->audio);
                            $obj->tags      = $track->tags->tag;
                            $obj->url       = $track->url;
                            $obj->duration  = $track->duration;
                            $obj->bitrate   = $bitrate;
                            $obj->width     = $width;
                            $obj->height    = $height;

                            $media[] = $obj;
                        }

                        $oc_event->publications[0]->attachments = $s_event->mediapackage->attachments->attachment;
                        $oc_event->publications[0]->media       = $media;
                    }

                    $event = self::prepareEpisode($oc_event);

                    $cache->write($cache_key, $event, 86000);
                } else {
                    $event = NULL;
                }
            }

            $events[$s_event->identifier] = $event;
        }
        return $events;
    }

    public function getAllScheduledEvents()
    {
        static $events;

        if (!$events) {
            $params = [
                'filter' => 'status:EVENTS.EVENTS.STATUS.SCHEDULED',
            ];

            $data = $this->getJSON('?' . http_build_query($params));

            if (is_array($data)) foreach ($data as $event) {
                $events[$event->identifier] = $event;
            }
        }

        return $events;
    }

    public function setVisibility($course_id, $episode_id, $visibility)
    {
        $acl = [
            [
                'allow'  => true,
                'role'   => $course_id . '_Instructor',
                'action' => 'read'
            ],

            [
                'allow'  => true,
                'role'   => $course_id . '_Instructor',
                'action' => 'write'
            ]
        ];

        if ($visibility == 'visible' || $visibility == 'free') {
            $acl[] = [
                'allow'  => true,
                'role'   => $course_id . '_Learner',
                'action' => 'read'
            ];
        }

        if ($visibility == 'free') {
            $acl[] = [
                'allow'  => true,
                'role'   => 'ROLE_ANONYMOUS',
                'action' => 'read'
            ];
        }

        // get current acl and filter out roles for this course, pertaining any other acl-roles
        $oc_acl = array_filter($this->getACL($episode_id), function ($entry) use ($course_id) {
            return (strpos($entry['role'], $course_id) === false);
        });

        $result = $this->putJSON('/' . $episode_id . '/acl', [
            'acl' => json_encode(array_merge($oc_acl, $acl))
        ], true);

        return $result[1] == 204;
    }

    public function getVisibilityForEpisode($episode, $course_id = null)
    {
        if (is_null($course_id)) {
            $course_id = Context::getId();
        }

        $acls = $episode->acl;

        if (empty($acls) || $acls === NULL) {
            return NULL;
        }

        $vis_conf = !is_null(CourseConfig::get($course_id)->COURSE_HIDE_EPISODES)
            ? boolval(CourseConfig::get($course_id)->COURSE_HIDE_EPISODES)
            : \Config::get()->OPENCAST_HIDE_EPISODES;
        $default = $vis_conf
            ? 'invisible'
            : 'visible';

        // check, if the video is free for all
        foreach ($acls as $acl) {
            if (
                $acl->role == 'ROLE_ANONYMOUS'
                && $acl->action == 'read'
                && $acl->allow == true
            ) {
                return 'free';
            }
        }

        // check, if the video is free for course
        foreach ($acls as $acl) {
            if (
                $acl->role == $course_id . '_Learner'
                && $acl->action == 'read'
                && $acl->allow == true
            ) {
                return 'visible';
            }
        }

        // check, if the video is free for lecturers
        foreach ($acls as $acl) {
            if (
                $acl->role == $course_id . '_Instructor'
                && $acl->action == 'read'
                && $acl->allow == true
            ) {
                return 'invisible';
            }
        }

        // nothing found, return default visibility
        OCModel::setVisibilityForEpisode($course_id, $episode->id, $default);
        return $default;
    }

    private function prepareEpisode($episode)
    {
        $new_episode = [
            'id'            => $episode->identifier,
            'series_id'     => $episode->is_part_of,
            'title'         => $episode->title,
            'start'         => $episode->start,
            'description'   => $episode->description,
            'author'        => $episode->creator,
            'has_previews'  => false
        ];

        foreach ($episode->publications as $publication) {
            if (!empty($publication->attachments)) {
                $presentation_preview  = false;
                $preview               = false;
                $presenter_download    = [];
                $presentation_download = [];
                $audio_download        = [];
                $annotation_tool       = false;
                $duration              = 0;

                foreach ((array) $publication->attachments as $attachment) {
                    if ($attachment->flavor === "presenter/search+preview" || $attachment->type === "presenter/search+preview") {
                        $preview = $attachment->url;
                    }
                    if ($attachment->flavor === "presentation/player+preview" || $attachment->type === "presentation/player+preview") {
                        $presentation_preview = $attachment->url;
                    }
                }

                foreach ($publication->media as $track) {
                    $parsed_url = parse_url($track->url);

                    if ($track->flavor === 'presenter/delivery') {
                        if (($track->mediatype === 'video/mp4' || $track->mediatype === 'video/avi')
                            && ((in_array('atom', $track->tags) || in_array('engage-download', $track->tags))
                                && $parsed_url['scheme'] != 'rtmp' && $parsed_url['scheme'] != 'rtmps')
                            && !empty($track->has_video)
                        ) {
                            $quality = $this->calculate_size(
                                $track->bitrate,
                                $track->duration
                            );
                            $presenter_download[$quality] = [
                                'url'  => $track->url,
                                'info' => $this->getResolutionString($track->width, $track->height)
                            ];

                            $duration = $track->duration;
                        }

                        if (
                            in_array($track->mediatype, ['audio/aac', 'audio/mp3', 'audio/mpeg', 'audio/m4a', 'audio/ogg', 'audio/opus'])
                            && !empty($track->has_audio)
                        ) {
                            $quality = $this->calculate_size(
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
                        $quality = $this->calculate_size(
                            $track->bitrate,
                            $track->duration
                        );

                        $presentation_download[$quality] = [
                            'url'  => $track->url,
                            'info' => $this->getResolutionString($track->width, $track->height)
                        ];
                    }
                }

                foreach ($episode->publications as $publication) {
                    if ($publication->channel == 'annotation-tool') {
                        $annotation_tool = $publication->url;
                    }
                }

                ksort($presenter_download);
                ksort($presentation_download);
                ksort($audio_download);

                $new_episode['preview']               = $preview;
                $new_episode['presentation_preview']  = $presentation_preview;
                $new_episode['presenter_download']    = $presenter_download;
                $new_episode['presentation_download'] = $presentation_download;
                $new_episode['audio_download']        = $audio_download;
                $new_episode['annotation_tool']       = $annotation_tool;
                $new_episode['has_previews']          = $episode->has_previews ?: false;
                $new_episode['duration']              = $duration;

                break;
            }
        }
        return $new_episode;
    }

    private function calculate_size($bitrate, $duration)
    {
        return ($bitrate / 8) * ($duration / 1000);
    }

    private function getResolutionString($width, $height)
    {
        return $width . ' * ' . $height . ' px';
    }
}
