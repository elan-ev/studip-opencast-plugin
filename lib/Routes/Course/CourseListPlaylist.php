<?php

namespace Opencast\Routes\Course;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\PlaylistSeminars;

/**
 * Find the playlists for the passed course
 */
class CourseListPlaylist extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $user;

        $course_id = $args['course_id'];
        // find all playlists of the seminar
        $seminar_playlists = PlaylistSeminars::findBySeminar_id($course_id);

        foreach ($seminar_playlists as $seminar_playlist) {
            // check what permissions the current user has on the playlist
            foreach($seminar_playlist->playlist->perms as $perm) {
                if ($perm->perm == 'owner' || $perm->perm == 'write' || $perm->perm == 'read') {
                    // Add playlist, if the user has access
                    $playlist_list[] = $seminar_playlist->toSanitizedArray();
                }
            }
        }
        
        return $this->createResponse($playlist_list, $response);
    }
}