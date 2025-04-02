<?php

namespace Opencast\Routes\Schedule;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\ScheduleHelper;
use Opencast\Providers\Perm;

class ScheduleAdd extends OpencastController
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

        $json = $this->getRequestData($request);

        $livestream = !empty($json['livestream']) ? true : false;

        $message = [
            'type' => 'error',
            'text' => _('Aufzeichnung konnte nicht geplant werden.')
        ];

        if (ScheduleHelper::scheduleEventForSeminar($course_id, $termin_id, $livestream)) {
            $message = [
                'type' => 'success',
                'text' => _('Aufzeichnung wurde geplant.')
            ];

            ScheduleHelper::sendRecordingNotifications($course_id);

            \StudipLog::log('OC_SCHEDULE_EVENT', $termin_id, $course_id);
        }

        return $this->createResponse(
            [
                'message' => $message
            ],
            $response->withStatus(201)
        );
    }
}
