<?php

namespace Opencast\Routes\Course;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\PlaylistSeminars;
use Opencast\Models\Tags;
use Opencast\Models\Filter;
use Opencast\Models\Playlists;
use Opencast\Models\Helpers;

/**
 * Find the playlists for the passed course
 */
class CourseListPlaylist extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $user, $perm;
        $course_id = $args['course_id'];

        $params = $request->getQueryParams();

        if (empty($params['cid'])) {
            $params['cid'] = $course_id;
        }

        // check if user has access to this seminar
        if (!$perm->have_studip_perm($course_id, 'user')) {
            throw new \AccessDeniedException();
        }

        // Order.
        $params['order'] = 'mkdate_asc';
        // find all playlists of the seminar
        $seminar_playlists = Playlists::getCoursePlaylists($course_id, new Filter($params), $user->id);
        $playlist_list = [];

        foreach ($seminar_playlists['playlists'] as $seminar_playlist) {
            $data = $seminar_playlist->toSanitizedArray();

            if ((bool) $seminar_playlist->is_default) {
                // Here, we put default at first.
                array_unshift($playlist_list, $data);
            } else {
                $playlist_list[] = $data;
            }
        }

        $courses_ids = PlaylistSeminars::getCoursePlaylistsCourses($course_id);

        return $this->createResponse([
            'playlists' => $playlist_list,
            'count'     => $seminar_playlists['count'],
            'tags'      => Tags::getCoursePlaylistsTags($course_id),
            'courses'   => PlaylistSeminars::getCoursesArray($courses_ids),
        ], $response);
    }
}
