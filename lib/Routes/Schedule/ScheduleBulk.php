<?php

namespace Opencast\Routes\Schedule;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\ScheduleHelper;

class ScheduleBulk extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $perm;

        if (!$perm->have_perm('tutor')) {
            throw new \AccessDeniedException();
        }

        $course_id = $args['course_id'];
        $json = $this->getRequestData($request);
        $termin_ids = isset($json['termin_ids']) ? $json['termin_ids'] : [];
        $action = isset($json['action']) ? $json['action'] : null;
        $available_actions = ['schedule', 'unschedule', 'update', 'live'];
        if (empty($course_id) || empty($action) || !in_array($action, $available_actions)) {
            throw new Error('Es fehlen Parameter!', 422);
        }

        $message_type = 'success';
        $message_text = _('Die angeforderte Massenaktion wurde ausgeführt.');
        $errors = [];

        // try to set higher time limit to prevent breaking the bulk update in the middle of it
        set_time_limit(1800);
        foreach ($termin_ids as $termin_id) {
            $result = false;
            switch ($action) {
                case 'schedule':
                    $result = ScheduleHelper::scheduleEventForSeminar($course_id, $termin_id);
                    break;
                case 'unschedule':
                    $result = ScheduleHelper::deleteEventForSeminar($course_id, $termin_id);
                    break;
                case 'update':
                    $result = ScheduleHelper::updateEventForSeminar($course_id, $termin_id);
                    break;
            }
            if (!$result) {
                $date = new \SingleDate($termin_id);
                $date_text = $date->getDatesExport();
                $errors[$termin_id] = $date_text;
            }
        }

        if ($action == 'schedule') {
            ScheduleHelper::sendRecordingNotifications($course_id);
        }

        if (!empty($errors)) {
            $message_type = count($errors) == count($termin_ids) ? 'error' : 'warning';
            $message_text = count($errors) == count($termin_ids)
                ? _('Die angeforderte Massenaktion ist fehlgeschlagen.')
                : _('Die angeforderte Massenaktion wurde mit einigen Fehlern ausgeführt.');
        }

        return $this->createResponse(
            [
                'message' => [
                    'type' => $message_type,
                    'text' => $message_text
                ]
            ],
            $response->withStatus(201)
        );
    }
}
