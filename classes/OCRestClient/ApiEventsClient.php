<?php

use Opencast\Models\OCConfig;

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
    public function getEpisode($episode_id)
    {
        list($data, $code) = $this->getJSON('/' . $episode_id, [], true, true);

        return [$code, $data];
    }

    /**
     *  getEpisodes() - retrieves episode metadata for a given series identifier
     *  from connected Opencast
     *
     * @param string series_id Identifier for a Series
     *
     * @return array response of episodes
     */
    public function getEpisodes($series_id, $refresh = false)
    {
        $cache     = StudipCacheFactory::getCache();
        $cache_key = 'oc_episodesforseries/' . $series_id;
        $episodes  = $cache->read($cache_key);

        if ($refresh || $episodes === false || $GLOBALS['perm']->have_perm('dozent')) {
            $service_url = "/episode.json?sid=" . $series_id . "&q=&episodes=true&sort=&limit=0&offset=0";
            $service_url = '/?sign=false&withacl=false&withmetadata=false&withscheduling=false&withpublications=true&filter=is_part_of:'
                . $series_id . '&sort=&limit=0&offset=0';

            if ($episodes = $this->getJSON($service_url)) {
                foreach ($episodes as $key => $val) {
                    $episodes[$key]->id = $val->identifier;
                }

                $cache->write($cache_key, serialize($episodes), 7200);
                return $episodes ?: [];
            } else {
                return [];
            }
        } else {
            return unserialize($episodes) ?: [];
        }
    }

    public function getAclForEpisode($series_id, $episode_id)
    {
        static $acl;

        if (!$acl[$series_id]) {
            $params = [
                'withacl' => 'true',
                'filter'  => sprintf(
                    'is_part_of:%s,status:EVENTS.EVENTS.STATUS.PROCESSED',
                    $series_id
                )
            ];

            $data = $this->getJSON('?' . http_build_query($params));

            if (is_array($data)) foreach ($data as $episode) {
                $acl[$series_id][$episode->identifier] = $episode->acl;
            }
        }

        return $acl[$series_id][$episode_id];
    }


    public function getACL($episode_id)
    {
        return json_decode(json_encode($this->getJSON('/' . $episode_id . '/acl')), true);
    }

    public function getBySeries($series_id)
    {
        $events = $this->getJSON('/?filter=is_part_of:' .
            $series_id . ',status:EVENTS.EVENTS.STATUS.PROCESSED', $params);

        return array_reduce($events, function ($events, $event) {
            $events[$event->identifier] = $event;
            return $events;
        }, []);
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

    public function getVisibilityForEpisode($series_id, $episode_id, $course_id = null)
    {
        if (is_null($course_id)) {
            $course_id = Context::getId();
        }

        $acls    = self::getAclForEpisode($series_id, $episode_id);
        $default = Config::get()->OPENCAST_HIDE_EPISODES
            ? 'invisible'
            : 'visible';

        if (empty($acls)) {
            OCModel::setVisibilityForEpisode($course_id, $episode_id, $default);
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
}
