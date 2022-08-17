<?php

namespace Opencast\Routes\Schedule;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\ScheduleHelper;

class ScheduleUpdate extends OpencastController
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
        $start = isset($json['start']) ? $json['start'] : null;
        $end = isset($json['end']) ? $json['end'] : null;

        $message = [
            'type' => 'error',
            'text' => _('Die geplante Aufzeichnung konnte nicht aktualisiert werden.')
        ];

        if (ScheduleHelper::updateEventForSeminar($course_id, $termin_id, $start, $end)) {
            $message = [
                'type' => 'success',
                'text' => _('Die geplante Aufzeichnung wurde aktualisiert.')
            ];
        }

        return $this->createResponse([
            'message' => $message
        ], $response->withStatus(200));
    }
}
