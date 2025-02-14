<?php

namespace Opencast\Routes\Course;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\SeminarSeries;

/**
 * Find the playlists for the passed course
 */
class CourseSetVisibility extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $user, $perm;

        $course_id = $args['course_id'];
        $visibility = $args['visibility'];

        if (!$perm->have_studip_perm('tutor', $course_id)) {
            throw new \AccessDeniedException();
        }

        $series = SeminarSeries::findOneBySeminar_id($course_id);
        if (empty($series)) {
            throw new Error(_('Das Seminar kann nicht gefunden werden'), 404);
        }
        $series->setValue('visibility', $visibility);
        $series->store();

        return $response->withStatus(204);
    }
}