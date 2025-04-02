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
     * Sets ACL for an episode in connected Opencast and republishes metadata
     *
     * @param string $episode_id id of episode
     * @param object $acl the acl object
     *
     * @return boolean
     */
    public function setACL($episode_id, $acl)
    {
        $workflow_client = ApiWorkflowsClient::getInstance($this->config_id);

        $response = $this->opencastApi->eventsApi->updateAcl($episode_id, array_values($acl));

        // republish metadata, if updating the ACL was succesful
        if (in_array($response['code'], ['200', '204']) === true) {
            $workflow_client->republish($episode_id);
            return true;
        }

        return false;
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
     * Get all scheduled events
     *
     * @return array
     */
    public function getAllScheduledEvents()
    {
        // filters are AND concatenated, to get all events, we need to split the calls
        $events = [];

        // EVENTS.EVENTS.STATUS.RECORDING should also be added here... which refers to live events
        $params[0] = [
            'filter' => ['status' => [
                'EVENTS.EVENTS.STATUS.SCHEDULED'
            ]],
        ];

        $params[1] = [
            'filter' => ['status' => [
                'EVENTS.EVENTS.STATUS.RECORDING'
            ]],
        ];

        $data = array_merge(
            $this->getAll($params[0]),
            $this->getAll($params[1])
        );

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

    /**
     * Return media files
     *
     * @param
     *
     * @return array
     */
    public function getMedia($eventId)
    {
        $response = $this->opencastApi->eventsApi->getMedia($eventId);

        if ($response['code'] == 200) {
            return $response['body'];
        }

        return false;
    }
}
