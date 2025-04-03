<?php

namespace Opencast\Routes\Schedule;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\ScheduleHelper;
use Opencast\Providers\Perm;

class ScheduleDelete extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $user;

        $termin_id = $args['termin_id'];
        $course_id = $args['course_id'];

        if (empty($termin_id) || empty($course_id)) {
            throw new Error('Es fehlen Parameter!', 422);
        }

        if (!Perm::schedulingAllowed($course_id, $user->id)) {
            throw new \AccessDeniedException();
        }

        if (ScheduleHelper::deleteEventForSeminar($course_id, $termin_id)) {
            return $response->withStatus(204);
        }

        throw new Error(_('Die geplante Aufzeichnung konnte nicht entfernt werden.'), 409);
    }
}
