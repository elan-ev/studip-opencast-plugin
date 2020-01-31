<?php

use Opencast\Models\OCConfig;

class ApiEventsClient extends OCRestClient
{
    static $me;
    public $serviceName = "ApiEvents";

    function __construct($config_id = 1)
    {
        if ($config = OCConfig::getConfigForService('apievents', $config_id)) {
            parent::__construct($config);
        } else {
            throw new Exception (_("Die Konfiguration wurde nicht korrekt angegeben"));
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

    public function getAllScheduledEvents()
    {
        static $events;

        if (!$events) {
            $params = [
                'filter'  => 'status:EVENTS.EVENTS.STATUS.SCHEDULED',
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

        $acls = self::getAclForEpisode($series_id, $episode_id);
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
