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

class PlaylistAddVideo extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $user, $perm;

        $playlist = Playlists::findOneByToken($args['token']);
        $video = Videos::findOneByToken($args['vid_token']);

        // check what permissions the current user has on the playlist and video
        if (!Authority::canAddVideoToPlaylist($user, $playlist, $video)) {
            throw new \AccessDeniedException();
        }

        // Add video in playlist of Opencast
        $playlist_client = ApiPlaylistsClient::getInstance($playlist->config_id);
        $oc_playlist = $playlist_client->getPlaylist($playlist->service_playlist_id);

        $entries = $oc_playlist->entries;

        // Only add video if not contained in entries
        $entry_exists = current(array_filter($entries, function($e) use ($video) {
            return $e->contentId === $video->episode;
        }));

        if (!$entry_exists) {
            $entries[] = [
                'contentId' => $video->episode,
                'type' => 'EVENT'
            ];

            $oc_playlist = $playlist_client->updateEntries($oc_playlist->id, $entries);
            if (!$oc_playlist) {
                throw new Error(_('Das Video konnte nicht hinzugefügt werden.'), 500);
            }
        }

        // Update playlist videos in DB
        $playlist->setEntries($oc_playlist->entries);

        return $response->withStatus(204);
    }
}