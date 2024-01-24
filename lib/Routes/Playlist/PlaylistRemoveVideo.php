<?php

namespace Opencast\Routes\Playlist;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\Playlists;
use Opencast\Models\Videos;
use Opencast\Models\PlaylistVideos;

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

        PlaylistVideos::deleteBySQL('playlist_id = ? AND video_id = ?', [
            $playlist->id, $video->id
        ]);

        return $response->withStatus(204);
    }
}
