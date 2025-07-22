<?php

namespace Opencast\Routes\Course;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\Helpers;
use Opencast\Models\Playlists;
use Opencast\Models\PlaylistSeminars;

class CourseUpdatePlaylist extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        $playlist = Playlists::findOneByToken($args['token']);
        $course_id = $args['course_id'];

        // check what permissions the current user has on the playlist
        $uperm = $playlist->getUserPerm();

        if (empty($uperm) || ($uperm != 'owner' && $uperm != 'write'))
        {
            throw new \AccessDeniedException();
        }

        $json = $this->getRequestData($request);

        $playlist_seminar = PlaylistSeminars::findOneBySQL('seminar_id = ? AND playlist_id = ?', [$course_id, $playlist->id]);

        if (empty($playlist_seminar)) {
            throw new Error(_('Die Wiedergabeliste kann in der Veranstaltung nicht gefunden werden'), 404);
        }

        // Forbid renaming of default course playlist
        if (array_key_exists('title', $json) && $playlist_seminar->is_default == '1') {
            unset($json['title']);
        }

        // Update playlist seminar data
        $playlist_seminar_data = $playlist_seminar->toArray();
        foreach ($json as $field => $value) {
            if (in_array($field, array_keys($playlist_seminar_data))) {
                $playlist_seminar_data[$field] = $value;
                unset($json[$field]);
            }
        }

        $playlist_seminar->setData($playlist_seminar_data);
        $playlist_seminar->store();

        // Store remaining data in playlist
        $playlist->setData($json);
        $playlist->store();

        // Make sure there is only one default playlist for a course at a time.
        if ($playlist_seminar->is_default == '1') {
            Helpers::ensureCourseHasOneDefaultPlaylist($course_id, $playlist->id);
        }

        $ret_playlist = $playlist_seminar->toSanitizedArray();

        return $this->createResponse($ret_playlist, $response->withStatus(200));
    }
}
