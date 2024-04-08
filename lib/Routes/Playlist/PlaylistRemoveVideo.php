<?php

namespace Opencast\Routes\Playlist;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\REST\ApiPlaylistsClient;
use Opencast\Models\Playlists;
use Opencast\Models\Videos;

class PlaylistRemoveVideo extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $user;

        $playlist = Playlists::findOneByToken($args['token']);
        $video = Videos::findOneByToken($args['vid_token']);

        // check what permissions the current user has on the playlist and video
        $uperm = $playlist->getUserPerm();

        if ((empty($uperm) ||
            $uperm != 'owner' && $uperm != 'write'))
        {
            throw new \AccessDeniedException();
        }

        // Prevent removing video from playlist when it is livestream.
        if ((bool) $video->is_livestream) {
            return $this->createResponse([
                'message' => [
                    'type' => 'error',
                    'text' => _('Entfernung des Livestream-Videos aus der Wiedergabeliste ist nicht erlaubt.')
                ],
            ], $response->withStatus(403));
        }

        // Add video in playlist of Opencast
        $playlist_client = ApiPlaylistsClient::getInstance($playlist->config_id);
        $oc_playlist = $playlist_client->getPlaylist($playlist->service_playlist_id);

        $existing_entries = $oc_playlist->entries;

        // Remove all occurrences of video from entries
        $entries = array_values(array_filter($existing_entries, function ($entry) use ($video) {
           return $entry->contentId !== $video->episode;
        }));

        if (count($entries) < count($existing_entries)) {
            $oc_playlist = $playlist_client->updateEntries($oc_playlist->id, $entries);
            if (!$oc_playlist) {
                throw new Error(_('Das Video konnte nicht entfernt werden.'), 500);
            }
        }

        // Update playlist videos in DB
        $playlist->setEntries($oc_playlist->entries);

        return $response->withStatus(204);
    }
}
