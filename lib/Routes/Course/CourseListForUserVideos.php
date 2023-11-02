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
 * Find the user's courses from all videos the user has access to
 */
class CourseListForUserVideos extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $perm;
        $courses = PlaylistSeminars::getUserVideosCourses();

        $ret = [];

        foreach ($courses as $course_id) {
            $course = \Course::find($course_id);

            // Check if user has access to this seminar
            if ($perm->have_studip_perm($course_id, 'user')) {
                $ret[] = [
                    'id'    => $course->id,
                    'name'  => $course->getFullname('number-name'),
                ];
            }
        }

        return $this->createResponse($ret, $response->withStatus(200));
    }
}
