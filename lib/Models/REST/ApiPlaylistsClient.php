<?php
namespace Opencast\Models\REST;

use Opencast\Models\Config;

class ApiPlaylistsClient extends RestClient
{
    public static $me;
    public        $serviceName = 'ApiPlaylists';

    public function __construct($config_id = 1)
    {
        if ($config = Config::getConfigForService('apiplaylists', $config_id)) {
            parent::__construct($config);
        } else {
            throw new \Exception ($this->serviceName . ': '
                . _('Die Opencast-Konfiguration wurde nicht korrekt angegeben'));
        }
    }

    /**
     * Get all playlists from connected opencast based on defined parameters
     * Playlists that you do not have read access to will not show up.
     *
     * @param array $params (optional) The list of query params to pass which can contain the followings:
     *  [
     *       'limit' => (int) {The maximum number of results to return for a single request},
     *       'offset' => (int) {The index of the first result to return},
     *       'sort' => {The sort criteria. A criteria is specified by a case-sensitive sort name and the order separated by a colon (e.g. updated:ASC). Supported sort names: 'updated'. Use the order ASC to sort ascending or DESC to sort descending.}
     *  ]
     *
     * @return array|boolean list of playlists
     */
    public function getAll($params = [])
    {
        $response = $this->opencastApi->playlistsApi->getAll($params);

        if ($response['code'] == 200) {
            return $response['body'];
        }

        return false;
    }

    /**
     * Get a playlist
     *
     * @param string $playlist_id id of playlist
     *
     * @return object|boolean playlist
     */
    public function getPlaylist($playlist_id)
    {
        $response = $this->opencastApi->playlistsApi->get($playlist_id);

        if ($response['code'] == 200) {
            return $response['body'];
        }

        return false;
    }

    /**
     * Create a playlist
     *
     * @param string|array $playlist playlist data
     *
     * @return object|boolean created playlist
     */
    public function createPlaylist($playlist)
    {
        $response = $this->opencastApi->playlistsApi->create($playlist);

        if ($response['code'] == 201) {
            return $response['body'];
        }

        return false;
    }

    /**
     * Update a playlist
     *
     * @param string $playlist_id id of playlist
     * @param string|array $playlist playlist data
     *
     * @return object|boolean updated playlist
     */
    public function updatePlaylist($playlist_id, $playlist)
    {
        $response = $this->opencastApi->playlistsApi->update($playlist_id, $playlist);

        if ($response['code'] == 200) {
            return $response['body'];
        }

        return false;
    }

    /**
     * Update entries of a playlist
     *
     * @param string $playlist_id id of playlist
     * @param string|array $entries playlist entries data
     *
     * @return object|boolean updated playlist
     */
    public function updateEntries($playlist_id, $entries)
    {
        $response = $this->opencastApi->playlistsApi->updateEntries($playlist_id, $entries);

        if ($response['code'] == 200) {
            return $response['body'];
        }

        return false;
    }

    /**
     * Delete a playlist
     *
     * @param string $playlist_id id of playlist
     *
     * @return object|boolean deleted playlist
     */
    public function deletePlaylist($playlist_id)
    {
        $response = $this->opencastApi->playlistsApi->delete($playlist_id);

        if ($response['code'] < 400) {
            return $response['body'];
        }

        return false;
    }
}
