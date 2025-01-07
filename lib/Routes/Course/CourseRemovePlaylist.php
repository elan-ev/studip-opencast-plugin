<?php

namespace Opencast\Routes\Course;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\Playlists;
use Opencast\Models\PlaylistSeminars;
use Opencast\Providers\Perm;

class CourseRemovePlaylist extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $user, $perm;

        $playlist = Playlists::findOneByToken($args['token']);
        $course_id = $args['course_id'];

        if (!Perm::editAllowed($course_id, $user->id)) {
            throw new \AccessDeniedException();
        }

        PlaylistSeminars::deleteBySql('playlist_id = :playlist_id AND seminar_id = :course_id',[
            ':playlist_id' => $playlist->id,
            ':course_id'   => $course_id
        ]);

        // Remove playlist completely to prevent ghost playlists because playlists are disabled in work place
        $playlist->delete();

        return $response->withStatus(204);
    }
}