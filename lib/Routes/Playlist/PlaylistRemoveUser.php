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

class PlaylistRemoveUser extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $user;

        $playlist = Playlists::findOneByToken($args['token']);

        // check what permissions the current user has on the playlist
        $perm = reset($playlist->perms->findBy('user_id', $user->id)->toArray());

        if (empty($perm) || (
            $perm['perm'] != 'owner' && $perm['perm'] != 'write'))
        {
            throw new \AccessDeniedException();
        }

        $user_id = \get_userid($args['username']);
        $perm = PlaylistsUserPerms::deleteBySQL('user_id = ? AND playlist_id = ?', [
            $user_id, $playlist->id
        ]);

        return $response->withStatus(204);
    }
}
