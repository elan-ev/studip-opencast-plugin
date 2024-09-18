<?php

use Opencast\Models\OCConfig;
use Opencast\Models\Pager;

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
            'acl' => json_encode(is_array($acl) ? $acl : $acl->toArray())
        ];

        $result = $this->putJSON('/' . $episode_id . '/acl', $data, true);

        return $result[1] == 200;
    }

    public function getSeriesEpisodes($series_id)
    {
        $params = [
            'filter' => 'is_part_of:' . $series_id,
        ];

        return json_decode(json_encode($this->getJSON('?' . http_build_query($params))), true);
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
        $cache = \StudipCacheFactory::getCache();

        $events = [];

        $offset = Pager::getOffset();
        $limit  = Pager::getLimit();
        $sort   = Pager::getSortOrder();
        $search = Pager::getSearch();
        $type   = $GLOBALS['perm']->have_studip_perm('tutor', $course_id)
            ? 'Instructor' : 'Learner';

        $search_service = new SearchClient($this->config_id);

        if (version_compare($this->getOcVersion(), '16', '<'))
        {
            /* * * * * * * * * * * * * * * * * * * * * * * * */
            /* * * *       O P E N C A S T   1 5         * * */
            /* * * * * * * * * * * * * * * * * * * * * * * * */
            // first, get list of events ids from search service

            $search_query = '';
            if ($search) {
                $search_query = " AND ( *:(dc_title_:($search)^6.0 dc_creator_:($search)^4.0 dc_subject_:($search)^4.0 dc_publisher_:($search)^2.0 dc_contributor_:($search)^2.0 dc_abstract_:($search)^4.0 dc_description_:($search)^4.0 fulltext:($search) fulltext:(*$search*) ) OR (id:$search) )";
            }

            $lucene_query = '(dc_is_part_of:' . $series_id . ')' . $search_query
                . ' AND oc_acl_read:' . $course_id . '_' . $type;

            $search_events = $search_service->getJSON('/lucene.json?q=' . urlencode($lucene_query)
                . "&sort=$sort&limit=$limit&offset=$offset");

            Pager::setLength($search_events->{'search-results'}->total);

            $results = is_array($search_events->{'search-results'}->result)
                ? $search_events->{'search-results'}->result
                : [$search_events->{'search-results'}->result];

            // then, iterate over list and get each event from the external-api
            foreach ($results as $s_event) {
                $cache_key = 'sop/episodes/' . $s_event->id;
                $event = $cache->read($cache_key);

                if (empty($s_event->id)) {
                    continue;
                }

                if (!$event) {
                    $oc_event = $this->getJSON('/' . $s_event->id . '/?withpublications=true');

                    if (!empty($oc_event->publications)) {
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

                                //echo '<pre>'; print_r($track); echo '</pre>';

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

                            $oc_event->publications[0]->media       = $media;

                            if (is_null($oc_event->publications[0]->attachments)) {
                                continue;
                            }
                            $oc_event->publications[0]->attachments = $s_event->mediapackage->attachments->attachment;
                        }
                    }

                    $event = self::prepareEpisode($oc_event);

                    $cache->write($cache_key, $event, 86000);
                }

                $events[$s_event->id] = $event;
            }
        } else {
            /* * * * * * * * * * * * * * * * * * * * * * * * */
            /* * * *       O P E N C A S T   1 6 +       * * */
            /* * * * * * * * * * * * * * * * * * * * * * * * */
            $search_events = $search_service->getJSON(
                "/episode.json?sid=$series_id&q=". urlencode($search)
                . "&sort=". urlencode($sort) ."&limit=$limit&offset=$offset"
            );

            Pager::setLength($search_events->total);

            $results = is_array($search_events->result)
                ? $search_events->result
                : [$search_events->result];

            // then, iterate over list and get each event from the external-api
            foreach ($results as $s_event) {
                $cache_key = 'sop/episodes/' . $s_event->mediapackage->id;
                $event = $cache->read($cache_key);

                if (empty($s_event->mediapackage->id)) {
                    continue;
                }

                if (!$event) {
                    $oc_event = $this->getJSON('/' . $s_event->mediapackage->id . '/?withpublications=true');

                    if (empty($oc_event->publications[0]->attachments)) {
                        $media = [];

                        if (empty($oc_event->mediapackage)) {
                            continue;
                        }

                        foreach ($s_event->mediapackage->media->track as $track) {
                            $width = 0;
                            $height = 0;
                            if (!empty($track->video)) {
                                list($width, $height) = explode('x', $track->video->resolution);
                                $bitrate = $track->video->bitrate;
                            } else if (!empty($track->audio)) {
                                $bitrate = $track->audio->bitrate;
                            }

                            //echo '<pre>'; print_r($track); echo '</pre>';

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

                        if (is_null($oc_event->publications[0]->media)) {
                            continue;
                        }

                        $oc_event->publications[0]->media       = $media;

                        if (is_null($oc_event->publications[0]->attachments)) {
                            continue;
                        }
                        $oc_event->publications[0]->attachments = $s_event->mediapackage->attachments->attachment;
                    }

                    $event = self::prepareEpisode($oc_event);

                    $cache->write($cache_key, $event, 86000);
                }

                $events[$s_event->mediapackage->id] = $event;
            }
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


    /**
     * getEventsInProcessing() - returns all events in status processing for a given SeriesID
     *
     * @return array API Events
     */
    public function getEventsInProcessing($seriesID)
    {
        static $events;

        if (!$events) {
            $params = [
                'filter' => "status:EVENTS.EVENTS.STATUS.PROCESSING,series:$seriesID",
            ];

            $events = $this->getJSON('?' . http_build_query($params));
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
            ],

            [
                'allow'  => $visibility == 'visible' || $visibility == 'free',
                'role'   => $course_id . '_Learner',
                'action' => 'read'
            ]
        ];

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

        $vis_conf = !is_null(CourseConfig::get($course_id)->COURSE_HIDE_EPISODES)
            ? boolval(CourseConfig::get($course_id)->COURSE_HIDE_EPISODES)
            : \Config::get()->OPENCAST_HIDE_EPISODES;
        $default = $vis_conf
            ? 'invisible'
            : 'visible';

        if (empty($acls)) {
            OCModel::setVisibilityForEpisode($course_id, $episode->id, $default);
            return $default;
        }

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

    public static function prepareEpisode($episode)
    {
        $new_episode = [
            'id'            => $episode->identifier,
            'series_id'     => $episode->is_part_of,
            'title'         => $episode->title,
            'start'         => $episode->start,
            'description'   => $episode->description,
            'author'        => is_array($episode->presenter) ? implode(', ', $episode->presenter) : $episode->creator,
            'has_previews'  => false
        ];
        $engage_publication = null;
        foreach ($episode->publications as $publication) {
            if ($publication->channel === 'engage-player') {
                $engage_publication = $publication;
            }
        }

        if (!empty($engage_publication->attachments)) {
            $presentation_preview  = false;
            $preview               = false;
            $presenter_download    = [];
            $presentation_download = [];
            $audio_download        = [];
            $supplemental_material = [];
            $i = -1;
            $annotation_tool       = false;
            $duration              = 0;

            foreach ((array) $engage_publication->attachments as $attachment) {
                if ($attachment->flavor === "presenter/search+preview" || $attachment->type === "presenter/search+preview") {
                    $preview = $attachment->url;
                }
                if ($attachment->flavor === "presentation/player+preview" || $attachment->type === "presentation/player+preview") {
                    $presentation_preview = $attachment->url;
                }
                if ($attachment->flavor === "presenter/search+preview" && !$preview) {
                    $preview = $attachment->url;
                }
                if ($attachment->flavor === "presenter/player+preview" && !$presentation_preview) {
                    $presentation_preview = $attachment->url;
                }
                if (explode('/', $attachment->flavor)[0] === 'captions') {
                    // Untertitel
                    $supplemental_material[--$i] = [
                        'url'  => $attachment->url,
                        'info' => 'Untertitel (' . explode('/', $attachment->flavor)[1] . ')',
                    ];
                }
                if ($attachment->flavor === "presentation/pdf" || $attachment->flavor === "attachment/notes") {
                    $supplemental_material[--$i] = [
                        'url'  => $attachment->url,
                        'info' => 'Vortragsfolien ' . explode('/', $attachment->mediatype)[1],
                    ];
                }
                if ($attachment->flavor === "etherpad/sharednotes") {
                    $supplemental_material[--$i] = [
                        'url'  => $attachment->url,
                        'info' => 'Geteilte Notizen (Etherpad Versionsgeschichte)',
                    ];
                }
                if ($attachment->flavor === "html/sharednotes") {
                    $supplemental_material[--$i] = [
                        'url'  => $attachment->url,
                        'info' => 'Geteilte Notizen',
                    ];
                }
            }

            foreach ($engage_publication->media as $track) {
                $parsed_url = parse_url($track->url);

                if ($track->flavor === 'presenter/delivery') {
                    if ($track->mediatype === 'video/mp4'
                        && $parsed_url['scheme'] != 'rtmp'
                        && $parsed_url['scheme'] != 'rtmps'
                        && !empty($track->has_video)
                    ) {
                      $quality = isset($track->size) ? $track->size : self::calculate_size(
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
                        $quality = isset($track->size) ? $track->size : self::calculate_size(
                            $track->bitrate,
                            $track->duration
                        );
                        $quality = $quality ? $quality : -rand();
                        $bitrate = $track->bitrate ? round($track->bitrate / 1000, 1) . 'kb/s' : 'vbr';
                        $encoder = isset($track->audio->encoder->type) ? ('/' . explode(' ', $track->audio->encoder->type)[0]) : '';
                        $type = explode('/', $track->mediatype)[1] . '/' . $encoder;
                        $audio_download[$quality] = [
                            'url'  => $track->url,
                            'info' => 'ReferentIn ' . $bitrate . ', ' . $type
                        ];

                        $duration = $track->duration;
                    }
                }

                if ($track->flavor === 'presentation/delivery') {
                  if (
                        $track->mediatype === 'video/mp4'
                         && $parsed_url['scheme'] != 'rtmp'
                         && $parsed_url['scheme'] != 'rtmps'
                         && !empty($track->has_video)
                  ) {
                      $quality = isset($track->size) ? $track->size : self::calculate_size(
                          $track->bitrate,
                          $track->duration
                      );

                      $presentation_download[$quality] = [
                          'url'  => $track->url,
                          'info' => self::getResolutionString($track->width, $track->height)
                      ];
                   }

                   if (in_array($track->mediatype, ['audio/aac', 'audio/mp3', 'audio/mpeg', 'audio/m4a', 'audio/ogg', 'audio/opus'])
                       && !empty($track->has_audio)
                   ) {
                       $quality = isset($track->size) ? $track->size : self::calculate_size(
                           $track->bitrate,
                           $track->duration
                       );
                       $quality = $quality ? $quality : -rand();
                       $bitrate = $track->bitrate ? round($track->bitrate / 1000, 1) . 'kb/s' : 'vbr';
                       $encoder = isset($track->audio->encoder->type) ? ('/' . explode(' ', $track->audio->encoder->type)[0]) : '';
                       $type = explode('/', $track->mediatype)[1] . $encoder;
                       $audio_download[$quality] = [
                           'url'  => $track->url,
                           'info' => 'Bildschirm ' . $bitrate . ', ' . $type
                       ];

                       $duration = $track->duration;
                   }
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

            $new_episode['preview']               = $preview ?: $presentation_preview;
            $new_episode['presenter_download']    = $presenter_download;
            $new_episode['presentation_download'] = $presentation_download;
            $new_episode['audio_download']        = $audio_download;
            $new_episode['supplemental_download'] = $supplemental_material;
            $new_episode['annotation_tool']       = $annotation_tool;
            $new_episode['has_previews']          = $episode->has_previews ?: false;
            $new_episode['duration']              = $duration;
        }

        return $new_episode;
    }

    private static function calculate_size($bitrate, $duration)
    {
        return ($bitrate / 8) * ($duration / 1000);
    }

    private static function getResolutionString($width, $height)
    {
        return $width . ' * ' . $height . ' px';
    }
}
