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

        $playlist_list = [];
        foreach ($playlists as $playlist) {
            // check what permissions the current user has on the playlist
            foreach($playlist->perms as $perm) {
                if ($perm->perm == 'owner' || $perm->perm == 'write' || $perm->perm == 'read') {
                    // Add playlist, if the user has access
                    $playlist['mkdate'] = ($playlist['mkdate'] == '0000-00-00 00:00:00')
                    ? 0 : \strtotime($playlist['mkdate']);
                    $playlist_list[$playlist->id] = $playlist->toSanitizedArray();
                }
            }
        }

        return $this->createResponse(@array_values($playlist_list) ?: [], $response);
    }
}
