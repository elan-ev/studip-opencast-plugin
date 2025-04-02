<?php

namespace Opencast\Routes\Course;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\Helpers;

/**
 * Find the playlists for the passed course
 */
class MyCourseList extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $user;

        $courses = Helpers::getMyCourses($user->id);

        $results = [];

        // NOTE: This class is also used in VideoAddToSeminar.vue, in order for dozents to select the courses.
        // Any changes applied to the result's formation of this class must be also implemented in that component!
        foreach ($courses as $course_id) {
            $course = \Course::find($course_id);
            $results['S'. ($course->end_semester->beginn ?? '0')][$course->getFullname('sem-duration-name')][] = [
                'id'       => $course->id,
                'name'     => $course->getFullname()
            ];

        }

        krsort($results);

        return $this->createResponse($results, $response);
    }
}