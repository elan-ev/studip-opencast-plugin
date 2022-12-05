<?php

namespace Opencast\Routes\Schedule;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\ScheduleHelper;

class ScheduleDelete extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $perm;

        if (!$perm->have_perm('tutor')) {
            throw new \AccessDeniedException();
        }

        $termin_id = $args['termin_id'];
        $course_id = $args['course_id'];

        if (empty($termin_id) || empty($course_id)) {
            throw new Error('Es fehlen Parameter!', 422);
        }

        if (ScheduleHelper::deleteEventForSeminar($course_id, $termin_id)) {
            return $response->withStatus(204);
        }
        
        throw new Error(_('Die geplante Aufzeichnung konnte nicht entfernt werden.'), 409);
    }
}
