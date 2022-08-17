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
        $livestream = isset($json['livestream']) ? $json['livestream'] : false;

        $message = [
            'type' => 'error',
            'text' => _('Aufzeichnung konnte nicht geplant werden.')
        ];

        if (ScheduleHelper::scheduleEventForSeminar($course_id, $termin_id, $livestream)) {
            $message = [
                'type' => 'success',
                'text' => $livestream ? _('Livestream mit Aufzeichnung wurde geplant.') : _('Aufzeichnung wurde geplant.')
            ];
            
            $course = \Course::find($course_id);
            $members = $course->members;
            $users = [];
            foreach ($members as $member) {
                $users[] = $member->user_id;
            }

            $notification = sprintf(
                _('Die Veranstaltung "%s" wird für Sie mit Bild und Ton automatisiert aufgezeichnet.'),
                htmlReady($course->name)
            );
            $plugin = \PluginEngine::getPlugin('OpenCast');
            $assetsUrl = rtrim($plugin->getPluginURL(), '/') . '/assets';
            $icon =  \Icon::create($assetsUrl . '/images/opencast-black.svg');
            \PersonalNotifications::add(
                $users,
                \PluginEngine::getURL('opencast', ['cid' => $course_id], 'course'),
                $notification,
                $course_id,
                $icon
            );

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
