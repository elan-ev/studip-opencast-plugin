<?php

namespace Opencast\Routes\Playlist;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\Playlists;
use Opencast\Models\PlaylistsUserPerms;
use Opencast\Models\PlaylistVideos;

class PlaylistUpdatePositions extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $user;

        $playlist = Playlists::findOneByToken($args['token']);

        // check what permissions the current user has on the playlist
        $perm = $playlist->getUserPerm();

        if (empty($perm) || (
            $perm != 'owner' && $perm != 'write'))
        {
            throw new \AccessDeniedException();
        }

        PlaylistVideos::reorder($args['token'], $this->getRequestData($request));

        return $response->withStatus(200);
    }
}
