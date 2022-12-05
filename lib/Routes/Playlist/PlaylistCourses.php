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
class PlaylistCourses extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $user;

        // find all playlists, the current user has access to
        $playlist = Playlists::findOneByToken($args['token']);

        if (!$playlist) {
            return $this->createResponse([], $response);
        }

        // check what permissions the current user has on the playlist
        $perm = $playlist->getUserPerm();

        if (empty($perm) || (
            $perm != 'owner' && $perm != 'write'))
        {
            throw new \AccessDeniedException();
        }

        $courses = [];
        foreach ($playlist->courses as $course) {
            // check what permissions the current user has on the playlist
            $courses[] = [
                'id'   => $course->id,
                'name' => $course->getFullname()
            ];
        }

        return $this->createResponse($courses, $response);
    }
}
