<?php

namespace Opencast\Routes\Schedule;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\ScheduleHelper;

class ScheduleAdd extends OpencastController
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

        $json = $this->getRequestData($request);

        $message = [
            'type' => 'error',
            'text' => _('Aufzeichnung konnte nicht geplant werden.')
        ];

        if (ScheduleHelper::scheduleEventForSeminar($course_id, $termin_id)) {
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
