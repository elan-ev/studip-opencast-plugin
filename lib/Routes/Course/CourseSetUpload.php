<?php

namespace Opencast\Routes\Course;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\SeminarSeries;

/**
 * Find the playlists for the passed course
 */
class CourseSetUpload extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $user, $perm;

        $course_id = $args['course_id'];
        $upload = $args['upload'] ? 1 : 0;

        $required_course_perm = \Config::get()->OPENCAST_TUTOR_EPISODE_PERM ? 'tutor' : 'dozent';
        if (!$perm->have_studip_perm($required_course_perm, $course_id)) {
            throw new \AccessDeniedException();
        }

        \CourseConfig::get($course_id)->store(
            'OPENCAST_ALLOW_STUDENT_UPLOAD',
            $upload
        );

        return $response->withStatus(204);
    }
}
