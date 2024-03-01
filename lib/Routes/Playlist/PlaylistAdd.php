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

/**
 * Find the playlists for the passed user
 */
class PlaylistAdd extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $user;

        $json = $this->getRequestData($request);

        // create new playlist
        $playlist = new Playlists;

        $playlist->setData($json);
        $playlist->token = $this->container->get('token');
        $playlist->store();

        // set current user as owner for this playlist
        $perm = new PlaylistsUserPerms;
        $perm->setData([
            'playlist_id' => $playlist->id,
            'user_id'     => $user->id,
            'perm'        => 'owner'
        ]);
        $perm->store();

        $ret_playlist = $playlist->toSanitizedArray();
        $ret_playlist['users'] = [[
            'user_id'  => $perm->user->id,
            'fullname' => $perm->user->getFullname(),
            'perm'     => $perm->perm
        ]];

        return $this->createResponse(
            $ret_playlist,
            $response->withStatus(201)
        );
    }
}
