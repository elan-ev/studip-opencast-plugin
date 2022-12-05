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
            return json_decode(json_encode($response['body']), true);
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
        $response = $this->opencastApi->eventsApi->updateAcl($episode_id, $acl);
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
        $params = [
            'filter' => ['status' => 'EVENTS.EVENTS.STATUS.SCHEDULED'],
        ];

        $data = $this->getAll($params);

        if (is_array($data)) foreach ($data as $event) {
            $events[$event->identifier] = $event;
        }

        return $events;
    }

    /**
     * Updates event metadata
     *
     * @param string $episode_id id of episode
     * @param object $metadata dublin-core metadata
     *
     * @return boolean
     */
    public function updateMetadata($episode_id, $metadata)
    {
        return $this->opencastApi->eventsApi->updateMetadata($episode_id, 'dublincore/episode', $metadata);
    }

    /**
     * Deletes a episode
     *
     * @param string $episode_id id of episode
     *
     * @return boolean
     */
    public function deleteEpisode($episode_id)
    {
        $response = $this->opencastApi->eventsApi->delete($episode_id);
        return $response['code'] < 400;
    }
}
