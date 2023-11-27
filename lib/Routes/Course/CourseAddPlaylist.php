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

class CourseAddPlaylist extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $user, $perm;

        $playlist = Playlists::findOneByToken($args['token']);
        $course_id = $args['course_id'];

        // check what permissions the current user has on the playlist and video
        $perm_playlist = reset($playlist->perms->findBy('user_id', $user->id)->toArray());

        if (empty($perm_playlist) || !$perm->have_studip_perm('tutor', $course_id))      // allow any perm for adding playlists to course user has access to
        {
            throw new \AccessDeniedException();
        }


        $playlist_seminar = PlaylistSeminars::findOneBySQL('seminar_id = ? AND playlist_id = ?', [$course_id, $playlist->id]);
        if (is_null($playlist_seminar)) {
            $playlist_seminar = new PlaylistSeminars;
        }

        $playlist_seminar->setData([
            'playlist_id' => $playlist->id,
            'seminar_id' => $course_id,
            'visibility' => 'visible' //TODO set visibility correctly
        ]);
        $playlist_seminar->store();

        return $response->withStatus(204);
    }
}