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

class PlaylistShow extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $user;

        $playlist = Playlists::findOneByToken($args['token']);

        // check what permissions the current user has on the playlist
        $uperm = $playlist->getUserPerm();

        if (empty($uperm) || !$uperm)
        {
            throw new \AccessDeniedException();
        }

        $ret_playlist = $playlist->toSanitizedArray();
        $ret_playlist['users'] = [[
            'user_id'  => $user->id,
            'fullname' => \get_fullname($user->id),
            'perm'     => $uperm
        ]];

        return $this->createResponse($ret_playlist, $response->withStatus(200));
    }
}
