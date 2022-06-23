<?php
namespace Opencast\Models\REST;

use Opencast\Models\Config;
use Opencast\Models\Helpers;
use Opencast\Models\Pager;

class ApiEventsClient extends RestClient
{
    public static $me;
    public        $serviceName = 'ApiEvents';

    public function __construct($config_id = 1)
    {
        if ($config = Config::getConfigForService('apievents', $config_id)) {
            parent::__construct($config);
        } else {
            throw new \Exception ($this->serviceName . ': '
                . _('Die Opencast-Konfiguration wurde nicht korrekt angegeben'));
        }
    }

    /**
     * Retrieves the episode object from opencast
     * 
     * @param string $episode_id id of episode
     * @param array $params containing extra flags to specify in the request:
     * [
     *    'sign' => (boolean) {Whether public distribution urls should be signed.},
     *    'withacl' => (boolean) {Whether the acl metadata should be included in the response.},
     *    'withmetadata' => (boolean) {Whether the metadata catalogs should be included in the response. },
     *    'withscheduling' => (boolean) {Whether the scheduling information should be included in the response. (version 1.1.0 and higher)},
     *    'withpublications' => (boolean) {Whether the publication ids and urls should be included in the response.}
     *]
     * @return object|bool episode object or false if unable to get.
     */
    public function getEpisode($episode_id, $params = [])
    {
        $response = $this->opencastApi->eventsApi->get($episode_id, $params);
        if ($response['code'] == 200) {
            return $response['body'];
        }
        return false;
    }

    /**
     * Retrieves episode ACL from connected Opencast
     * 
     * @param string $episode_id id of episode
     * 
     * @return array|bool
     */
    public function getACL($episode_id)
    {
        $response = $this->opencastApi->eventsApi->getAcl($episode_id);
        if ($response['code'] == 200) {
            return $response['body'];
        }

        return false;
    }

    /**
     * Sets ACL for an episode in connected Opencast
     * 
     * @param string $episode_id id of episode
     * @param object $acl the acl object
     * 
     * @return boolean
     */
    public function setACL($episode_id, $acl)
    {
        $response = $this->opencastApi->eventsApi->updateAcl($episode_id, $acl->toArray());
        return $response['code'] == 200;
    }

    /**
     * Retrieves a list of episode based on defined parameters and pagination.
     * This method is intended to be consumed by front end.
     * By default api/event GET is responsible to get the episodes,
     * however, when advance search is defined in config, lucene search will be used to get the episodes.
     *
     * @param string series_id Identifier for a Series
     * @param string course_id Course ID
     *
     * @return array list of consumable episodes
     */
    public function getEpisodes($series_id = null, $course_id = null)
    {
        $events = [];

        if ($this->advance_search) {
            $events = $this->episodesLookupAdvanced($series_id, $course_id);
        } else {
            $events = $this->episodesLookup($series_id);
        }

        return $events;
    }

    /**
     * Look for episodes with more in depth query using Search Client
     *
     * @param string $series_id series Identifier
     * @param string $course_id course Identifier
     *
     * @return array|boolean episodes lucene search response
     */
    private function episodesLookupAdvanced($series_id, $course_id)
    {
        $cache = \StudipCacheFactory::getCache();

        $lucene_query_array = [];

        $offset = Pager::getOffset();
        $limit  = Pager::getLimit();
        $sort   = Pager::getSortOrder();
        $search = Pager::getSearch();

        if (!empty($series_id)) {
            $lucene_query_array[] = '(dc_is_part_of:' . $series_id . ')';
        }

        if (!empty($search)) {
            $lucene_query_array[] = "( *:(dc_title_:($search)^6.0 dc_creator_:($search)^4.0 dc_subject_:($search)^4.0 dc_publisher_:($search)^2.0 dc_contributor_:($search)^2.0 dc_abstract_:($search)^4.0 dc_description_:($search)^4.0 fulltext:($search) fulltext:(*$search*) ) OR (id:$search) )";
        }

        if (!empty($course_id)) {
            $type = $GLOBALS['perm']->have_studip_perm('tutor', $course_id)
                ? 'Instructor' : 'Learner';
            $lucene_query_array[] = 'oc_acl_read:' . $course_id . '_' . $type;
        }

        $lucene_query = implode(' AND ', $lucene_query_array);
            
        $search_service = new SearchClient($this->config_id);
        $params = [
            'q' => $lucene_query,
            'sort' => $sort,
            'limit' => $limit,
            'offset' => $offset
        ];
        
        $search_results = $search_service->getLucene($params);
        $results = [];
        $total = 0;
        if (isset($search_results->{'search-results'}->result)) {
            $results = is_array($search_results->{'search-results'}->result)
            ? $search_results->{'search-results'}->result
            : [$search_results->{'search-results'}->result];

            $total = $search_results->{'search-results'}->total;
        }

        Pager::setLength($total);

        if (empty($results)) {
            return $results;
        }
        
        $events = [];
        foreach ($results as $s_event) {
            $cache_key = 'sop/episodes/' . $s_event->id;
            $event = $cache->read($cache_key);

            if (empty($s_event->id)) {
                continue;
            }

            if (!$event) {
                $oc_event = $this->getEpisode($s_event->id, ['withpublications' => true]);

                if (empty($oc_event->publications[0]->attachments)) {
                    $oc_event = $this->generatePublication($oc_event, $s_event);
                }

                $event = self::prepareEpisode(json_decode(json_encode($oc_event), true));

                $cache->write($cache_key, $event, 86000);
            }

            $events[$s_event->id] = $event;
        }

        return $events;
    }

    /**
     * Look for episodes
     *
     * @param string $series_id series Identifier
     *
     * @return array|boolean episodes
     */
    private function episodesLookup($series_id = null)
    {
        $cache = \StudipCacheFactory::getCache();

        $offset = Pager::getOffset();
        $limit  = Pager::getLimit();
        $sort   = Pager::getSortOrder(true);
        $search = Pager::getSearch();

        $filter = [];
        if (!empty($search)) {
            $filter['textFilter'] = $search;
        }
        if (!empty($series_id)) {
            $filter['is_part_of'] = $series_id;
        }

        $search_params = [
            'withpublications' => true,
            'sort' => $sort,
            // 'limit' => $limit,
            // 'offset' => $offset,
            'filter' => $filter
        ];

        $get_results = $this->getAll($search_params);
        Pager::setLength(count($get_results));
        $results = [];
        if (empty($get_results)) {
            return $results;
        }

        $results = array_slice($get_results, $offset, $limit);
        $search_service = new SearchClient($this->config_id);
        $events = [];
        foreach ($results as $oc_event) {
            if (!isset($oc_event->id)) {
                $oc_event->id = $oc_event->identifier;
            }

            $cache_key = 'sop/episodes/' . $oc_event->id;
            $event = $cache->read($cache_key);

            if (empty($oc_event->id)) {
                continue;
            }

            if (!$event) {
                if (empty($oc_event->publications[0]->attachments)) {
                    $s_event_array = $search_service->getEpisodes(['id' => $oc_event->id]);
                    if (!empty($s_event_array)) {
                        $s_event = $s_event_array[0];
                        $oc_event = $this->generatePublication($oc_event, $s_event);
                    }
                }

                $event = self::prepareEpisode(json_decode(json_encode($oc_event), true));

                $cache->write($cache_key, $event, 86000);
            }

            $events[$oc_event->id] = $event;
        }
        
        return $events;
    }

    /**
     * Get all episodes from connected opencast based on defined parameters
     *
     * @param array $param an array of query params
     *
     * @return array|boolean list of episodes
     */
    public function getAll($params = [])
    {
        $response = $this->opencastApi->eventsApi->getAll($params);

        if ($response['code'] == 200) {
            return $response['body'];
        }

        return false;
    }

    /**
     * Generates Publication for a single event
     *
     * @param object $oc_event opencast event object
     * @param object $s_event opencast search event object
     *
     * @return object $oc_event opencast event object with generate publication
     */
    private function generatePublication($oc_event, $s_event)
    {
        $media = [];

        if (!isset($s_event->mediapackage->media->track)) {
            return $oc_event;
        }

        $tracks = is_array($s_event->mediapackage->media->track)
            ? $s_event->mediapackage->media->track
            : [$s_event->mediapackage->media->track];

        foreach ($tracks as $track) {
            $width = 0;
            $height = 0;
            if (!empty($track->video)) {
                list($width, $height) = explode('x', $track->video->resolution);
                $bitrate = $track->video->bitrate;
            } else if (!empty($track->audio)) {
                $bitrate = $track->audio->bitrate;
            }

            $obj = new \stdClass();
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

        return $oc_event; 
    }

    /**
     * Get all scheduled events
     * 
     * @return array
     */
    public function getAllScheduledEvents()
    {
        static $events;

        if (!$events) {

            $params = [
                'filter' => ['status' => 'EVENTS.EVENTS.STATUS.SCHEDULED'],
            ];

            $data = $this->getAll($params);

            if (is_array($data)) foreach ($data as $event) {
                $events[$event->identifier] = $event;
            }
        }

        return $events;
    }

    /**
     * Prepares episode to be consumed by throughout the app
     * 
     * @param array $episode event default object from opencast
     * 
     * @return array new consumable episode array
     */
    private function prepareEpisode($episode)
    {
        $new_episode = [
            'id'            => $episode['identifier'],
            'series_id'     => $episode['is_part_of'],
            'title'         => $episode['title'],
            'start'         => $episode['start'],
            'description'   => $episode['description'],
            'author'        => $episode['creator'],
            'contributor'   => $episode['contributor'],
            'has_previews'  => false,
            'created'       => $episode['created']
        ];

        if (!empty($episode['publications'][0]['attachments'])) {
            $presentation_preview  = false;
            $preview               = false;
            $presenter_download    = [];
            $presentation_download = [];
            $audio_download        = [];
            $annotation_tool       = false;
            $track_link            = false;
            $duration              = 0;

            foreach ((array) $episode['publications'][0]['attachments'] as $attachment) {
                if ($attachment['flavor'] === "presenter/search+preview" || $attachment['type'] === "presenter/search+preview") {
                    $preview = $attachment['url'];
                }
                if ($attachment['flavor'] === "presentation/player+preview" || $attachment['type'] === "presentation/player+preview") {
                    $presentation_preview = $attachment['url'];
                }
            }

            foreach ($episode['publications'][0]['media'] as $track) {
                $parsed_url = parse_url($track['url']);

                if ($track['flavor'] === 'presenter/delivery') {
                    if (($track['mediatype'] === 'video/mp4' || $track['mediatype'] === 'video/avi')
                        && ((in_array('atom', $track['tags']) || in_array('engage-download', $track['tags']))
                        && $parsed_url['scheme'] != 'rtmp' && $parsed_url['scheme'] != 'rtmps')
                        && !empty($track['has_video'])
                    ) {
                        $quality = $this->calculateSize(
                            $track['bitrate'],
                            $track['duration']
                        );
                        $presenter_download[$quality] = [
                            'url'  => $track['url'],
                            'info' => $this->getResolutionString($track['width'], $track['height'])
                        ];

                        $duration = $track['duration'];

                    }

                    if (in_array($track['mediatype'], ['audio/aac', 'audio/mp3', 'audio/mpeg', 'audio/m4a', 'audio/ogg', 'audio/opus'])
                        && !empty($track['has_audio'])
                    ) {
                        $quality = $this->calculateSize(
                            $track['bitrate'],
                            $track['duration']
                        );
                        $audio_download[$quality] = [
                            'url'  => $track['url'],
                            'info' => round($track['audio']['bitrate'] / 1000, 1) . 'kb/s, ' . explode('/', $track['mediatype'])[1]
                        ];

                        $duration = $track['duration'];
                    }
                }

                if ($track['flavor'] === 'presentation/delivery' && (
                    (
                        $track['mediatype'] === 'video/mp4'
                        || $track['mediatype'] === 'video/avi'
                    ) && (
                        (
                            in_array('atom', $track['tags'])
                            || in_array('engage-download', $track['tags'])
                        )
                        && $parsed_url['scheme'] != 'rtmp'
                        && $parsed_url['scheme'] != 'rtmps'
                    )
                    && !empty($track['has_video'])
                )) {
                    $quality = $this->calculateSize(
                        $track['bitrate'],
                        $track['duration']
                    );

                    $presentation_download[$quality] = [
                        'url'  => $track['url'],
                        'info' => $this->getResolutionString($track['width'], $track['height'])
                    ];

                    $duration = $track['duration'];
                }
            }

            foreach ($episode['publications'] as $publication) {
                if ($publication['channel'] == 'engage-player') {
                    $track_link = $publication['url'];
                }
                if ($publication['channel'] == 'annotation-tool') {
                    $annotation_tool = $publication['url'];
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
            $new_episode['has_previews']          = $episode['has_previews'] ?: false;
            $new_episode['track_link']            = $track_link;
            $new_episode['duration']              = $duration;
        }

        return $new_episode;
    }

    /**
     * Calculates the size of a track
     * 
     * @param int $bitrate the bit rate of a track
     * @param int $duration the duration of a track
     * 
     * @return int size of a track
     */
    private function calculateSize($bitrate, $duration)
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
    private function getResolutionString($width, $height)
    {
        return $width .' * '. $height . ' px';
    }

    /**
     * Returns the visiblity status of an episode
     * 
     * @param string $series_id series id
     * @param string $episode_id episode id
     * @param string $course_id course id
     * 
     * @return string visibility status
     */
    public function getVisibilityForEpisode($series_id, $episode_id, $course_id)
    {
        $acls = self::getAclForEpisodeInSeries($series_id, $episode_id);

        $vis_conf = !is_null(CourseConfig::get($course_id)->COURSE_HIDE_EPISODES)
            ? boolval(CourseConfig::get($course_id)->COURSE_HIDE_EPISODES)
            : \Config::get()->OPENCAST_HIDE_EPISODES;
        $default = $vis_conf
            ? 'invisible'
            : 'visible';

        if (empty($acls)) {
            Helper::setVisibilityForEpisode($course_id, $episode_id, $default);
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
        Helpers::setVisibilityForEpisode($course_id, $episode_id, $default);
        return $default;
    }

    /**
     * Retrieves episode ACL from connected Opencast based on series
     * 
     * @param string $series_id id of series
     * @param string $episode_id id of episode
     * 
     * @return array 
     */
    private function getAclForEpisodeInSeries($series_id, $episode_id)
    {
        static $acl;

        if (!$acl[$series_id]) {
            $params = [
                'withacl' => true,
                'filter' => [
                    'is_part_of' => $series_id,
                    'status' => 'EVENTS.EVENTS.STATUS.PROCESSED'
                ]
            ];

            $data = $this->getAll($params);

            if (is_array($data)) foreach ($data as $episode) {
                $acl[$series_id][$episode->identifier] = $episode->acl;
            }
        }

        return $acl[$series_id][$episode_id];
    }
}
