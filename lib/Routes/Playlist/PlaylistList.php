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
class PlaylistList extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $user;

        // find all playlists, the current user has access to
        $playlists = Playlists::findByUser_id($user->id);

        foreach ($playlists as $playlist) {
            $perms = [];

            foreach($playlist->perms as $perm) {
                $perms[] = [
                    'user_id'  => $perm->user->id,
                    'fullname' => $perm->user->getFullname(),
                    'perm'     => $perm->perm
                ];
            }

            $playlist_list[] = [
                $playlist->toSanitizedArray(),
                'users' => $perms
            ];
        }

        return $this->createResponse([
            'playlists' => $playlist_list,
        ], $response);
    }
}
