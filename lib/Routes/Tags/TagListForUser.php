<?php

namespace Opencast\Routes\Tags;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\Playlists;
use Opencast\Models\Videos;
use Opencast\Models\Tags;

class TagListForUser extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $user;

        return $this->createResponse([
            '1' => 'PHP',
            '2' => 'VueJS'
        ], $response->withStatus(200));

        /*
        $playlists = Playlists::findByUser_id($user->id);

        foreach ($playlists as $playlist) {
            // check what permissions the current user has on the playlist
            foreach($playlist->perms as $perm) {
                if ($perm->perm == 'owner') {

                }
            }
        }
        $ret_playlist = $playlist->toSanitizedArray();
        $ret_playlist['users'] = [[
            'user_id'  => $perm['user_id'],
            'fullname' => \get_fullname($perm['user_id']),
            'perm'     => $perm['perm']
        ]];

        return $this->createResponse($ret_playlist, $response->withStatus(200));
        */
    }
}
