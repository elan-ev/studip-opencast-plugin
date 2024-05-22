<?php

namespace Opencast\Routes\Playlist;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\REST\ApiPlaylistsClient;
use Opencast\Models\Playlists;
use Opencast\Models\Videos;

class PlaylistRemoveVideos extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        $playlist = Playlists::findOneByToken($args['token']);

        // check what permissions the current user has on the playlist
        $uperm = $playlist->getUserPerm();

        if ((empty($uperm) ||
            $uperm != 'owner' && $uperm != 'write'))
        {
            throw new \AccessDeniedException();
        }

        $video_tokens = $this->getRequestData($request);
        $videos = array_map(function ($token) { return Videos::findOneByToken($token); }, $video_tokens);

        // Get playlist entries from Opencast
        $playlist_client = ApiPlaylistsClient::getInstance($playlist->config_id);
        $oc_playlist = $playlist_client->getPlaylist($playlist->service_playlist_id);

        $old_entries = $oc_playlist->entries;
        $entries = $oc_playlist->entries;

        foreach ($videos as $video) {
            // Prevent removing video from playlist when it is livestream.
            if ((bool) $video->is_livestream) {
                return $this->createResponse([
                    'message' => [
                        'type' => 'error',
                        'text' => _('Entfernung eines Livestream-Videos aus der Wiedergabeliste ist nicht erlaubt.')
                    ],
                ], $response->withStatus(403));
            }

            // Remove all occurrences of video from entries
            $entries = array_values(array_filter($entries, function ($entry) use ($video) {
                return $entry->contentId !== $video->episode;
            }));
        }

        if (count($entries) < count($old_entries)) {
            // Remove videos in playlist of Opencast
            $oc_playlist = $playlist_client->updateEntries($oc_playlist->id, $entries);
            if (!$oc_playlist) {
                throw new Error(_('Die Videos konnten nicht entfernt werden.'), 500);
            }
        }

        // Update playlist videos in DB
        $playlist->setEntries($oc_playlist->entries);

        return $response->withStatus(204);
    }
}
