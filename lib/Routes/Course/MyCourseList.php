<?php

namespace Opencast\Routes\Course;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;

/**
 * Find the playlists for the passed course
 */
class MyCourseList extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $user;

        $stmt = \DBManager::get()->prepare("SELECT seminar_id FROM seminar_user
            WHERE user_id = :user_id
                AND (seminar_user.status = 'dozent' OR seminar_user.status = 'tutor')
        ");

        $stmt->execute([':user_id' => $user->id]);

        $courses = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        $results = [];
        foreach ($courses as $course_id) {
            $course = \Course::find($course_id);
            $results['S'. $course->end_semester->beginn ?: '0'][$course->getFullname('sem-duration-name')][] = [
                'id'       => $course->id,
                'name'     => $course->getFullname()
            ];
        }

        krsort($results);

        return $this->createResponse($results, $response);
    }
}