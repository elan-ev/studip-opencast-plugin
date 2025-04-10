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
 * Set the default visibility of videos in a course using the course config
 */
class CourseSetDefaultVideosVisibility extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $user, $perm;

        $course_id = $args['course_id'];

        $json = $this->getRequestData($request);
        $visibility_option = $json['visibility_option'];
        $possible_visibility_options = ['default', 'visible', 'hidden'];

        if (!in_array($visibility_option, $possible_visibility_options)) {
            throw new Error('Ungültige Sichtbarkeit!', 422);
        }

        if (empty($course_id) || empty($visibility_option)) {
            throw new Error('Es fehlen Parameter!', 422);
        }

        if (!$perm->have_studip_perm('tutor', $course_id)) {
            throw new \AccessDeniedException();
        }

        $response_code = 204;
        $message = [
            'type' => 'success',
            'text' => _('Die Standardsichtbarkeit der Videos wurde aktualisiert.')
        ];
        try {
            \CourseConfig::get($course_id)->store(
                'OPENCAST_COURSE_DEFAULT_EPISODES_VISIBILITY',
                $visibility_option
            );
        } catch (\Throwable $e) {
            $response_code = 500;
            $message = [
                'type' => 'error',
                'text' => _('Beim Aktualisieren der Standardsichtbarkeit ist ein Fehler aufgetreten.')
            ];
        }

        return $this->createResponse([
            'message' => $message,
        ], $response->withStatus($response_code));
    }
}
