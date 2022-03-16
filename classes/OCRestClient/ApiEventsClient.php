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
            throw new Exception (_('Die Konfiguration wurde nicht korrekt angegeben'));
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
    public function getBySeries($series_id)
    {
        static $static_events;

        $cache = \StudipCacheFactory::getCache();

        if (empty($static_events[$series_id])) {
            $events = [];

            $offset = Pager::getOffset();
            $limit  = Pager::getLimit();
            $sort   = Pager::getSortOrder();
            $search = Pager::getSearch();

            $search_service = new SearchClient($this->config_id);

            // first, get list of events ids from search service
            $search_events = $search_service->getJSON('/episode.json?sid=' . $series_id
                . ($search ? '&q='. $search : '')
                . "&sort=$sort&limit=$limit&offset=$offset");

            Pager::setLength($search_events->{'search-results'}->total);

            $results = is_array($search_events->{'search-results'}->result)
                ? $search_events->{'search-results'}->result
                : [$search_events->{'search-results'}->result];

            // then, iterate over list and get each event from the external-api
            foreach ($results as $s_event) {
                $cache_key = 'sop/episodes/'. $s_event->id;
                $event = $cache->read($cache_key);

                if (empty($s_event->id)) {
                    continue;
                }

                if (!$event) {
                    $event = self::prepareEpisode(
                        $this->getJSON('/' . $s_event->id . '/?withpublications=true')
                    );

                    $cache->write($cache_key, $event, 86000);
                }

                $events[$s_event->id] = $event;
            }

            $static_events[$series_id] = $events;
        }

        return $static_events[$series_id];
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
            if ($acl->role == 'ROLE_ANONYMOUS'
                && $acl->action == 'read'
                && $acl->allow == true
            ) {
                return 'free';
            }
        }

        // check, if the video is free for course
        foreach ($acls as $acl) {
            if ($acl->role == $course_id . '_Learner'
                && $acl->action == 'read'
                && $acl->allow == true
            ) {
                return 'visible';
            }
        }

        // check, if the video is free for lecturers
        foreach ($acls as $acl) {
            if ($acl->role == $course_id . '_Instructor'
                && $acl->action == 'read'
                && $acl->allow == true
            ) {
                return 'invisible';
            }
        }

        // nothing found, return default visibility
        OCModel::setVisibilityForEpisode($course_id, $episode_id, $default);
        return $default;
    }

    private function prepareEpisode($episode)
    {
        $new_episode = [];

        if (!empty($episode->publications[0]->attachments)) {
            $presentation_preview  = false;
            $preview               = false;
            $presenter_download    = [];
            $presentation_download = [];
            $audio_download        = [];
            $annotation_tool       = false;

            foreach ((array) $episode->publications[0]->attachments as $attachment) {
                if ($attachment->flavor === "presenter/search+preview") {
                    $preview = $attachment->url;
                }
                if ($attachment->flavor === "presentation/player+preview") {
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
                        $quality = $this->calculate_size(
                            $track->bitrate,
                            $track->duration
                        );
                        $presenter_download[$quality] = [
                            'url'  => $track->url,
                            'info' => $this->getResolutionString($track->width, $track->height)
                        ];
                    }

                    if (in_array($track->mediatype, ['audio/aac', 'audio/mp3', 'audio/mpeg', 'audio/m4a', 'audio/ogg', 'audio/opus'])
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
                    }
                }

                if ($track->flavor === 'presentation/delivery' && (
                    (
                        $track->mediatype === 'video/mp4'
                        || $track->mediatype === 'video/avi'
                    ) && (
                        (
                            in_array('atom', $track->tags)
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
            $new_episode = [
                'id'                    => $episode->identifier,
                'series_id'             => $episode->is_part_of,
                'title'                 => $episode->title,
                'start'                 => $episode->start,
                'duration'              => $episode->duration,
                'description'           => $episode->description,
                'author'                => $episode->creator,
                'preview'               => $preview,
                'presentation_preview'  => $presentation_preview,
                'presenter_download'    => $presenter_download,
                'presentation_download' => $presentation_download,
                'audio_download'        => $audio_download,
                'annotation_tool'       => $annotation_tool,
                'has_previews'          => $episode->has_previews ?: false
            ];
        }

        return $new_episode;
    }

    private function calculate_size($bitrate, $duration)
    {
        return ($bitrate / 8) * ($duration / 1000);
    }

    private function getResolutionString($width, $height)
    {
        return $width .' * '. $height . ' px';
    }
}
