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
        $courses_ids = PlaylistSeminars::getUserVideosCourses();

        return $this->createResponse(PlaylistSeminars::getCoursesArray($courses_ids), $response->withStatus(200));
    }
}
