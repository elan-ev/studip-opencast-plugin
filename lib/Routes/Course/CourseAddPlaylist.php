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
use Opencast\Models\Helpers;
use Opencast\Providers\Perm;

class CourseAddPlaylist extends OpencastController
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

        $json = $this->getRequestData($request);

        $is_default = 0;
        // Make sure the default playlist is eligible.
        if (isset($json['is_default']) && (bool) $json['is_default'] == true) {
            $is_default = 1;
        }

        // check what permissions the current user has on the playlist
        $uperm = $playlist->getUserPerm();
        if (empty($uperm))
        {
            throw new \AccessDeniedException();
        }

        $seminar_playlist_data = [
            'playlist_id' => $playlist->id,
            'seminar_id' => $course_id,
            'visibility' => 'visible', //TODO set visibility correctly,
        ];

        $playlist_seminar = PlaylistSeminars::findOneBySQL('seminar_id = ? AND playlist_id = ?', [$course_id, $playlist->id]);
        if (empty($playlist_seminar)) {
            $playlist_seminar = new PlaylistSeminars;
        }

        // if the current record is default, we doen't change it and always remains the same.
        $seminar_playlist_data['is_default'] = $playlist_seminar->is_default ? 1 : $is_default;

        $playlist_seminar->setData($seminar_playlist_data);
        $playlist_seminar->store();

        // Make sure there is only one default playlist for a course at a time.
        if ((bool) $seminar_playlist_data['is_default']) {
            Helpers::ensureCourseHasOneDefaultPlaylist($course_id, $playlist->id);
        }

        return $response->withStatus(204);
    }
}
