<?php

namespace Opencast\Routes\Playlist;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\ScheduleHelper;
use Opencast\Models\Playlists;
use Opencast\Providers\Perm;

class PlaylistScheduleUpdate extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $user;

        $course_id = $args['course_id'];
        $token = $args['token'];
        $type = $args['type'];

        if (empty($token) || empty($course_id) || empty($type) || !in_array($type, ['livestreams', 'scheduled'])) {
            throw new Error('Es fehlen Parameter!', 422);
        }

        if (!Perm::schedulingAllowed($course_id, $user->id)) {
            throw new \AccessDeniedException();
        }

        $playlist = Playlists::findOneByToken($token);

        if (empty($playlist)) {
            throw new Error(_('Die Wiedergabeliste kann nicht gefunden werden'), 404);
        }

        if (empty($playlist->getUserPerm())) {
            throw new \AccessDeniedException();
        }

        $message = [
            'type' => 'error',
            'text' => _('Die Wiedergabeliste konnte nicht angewendet werden.')
        ];

        if (ScheduleHelper::setScheduledRecordingsPlaylist($playlist->id, $course_id, $type)) {
            $message = [
                'type' => 'success',
                'text' => _('Die Wiedergabeliste ist ausgewählt.')
            ];
        }

        return $this->createResponse(
            [
                'message' => $message
            ],
            $response->withStatus(201)
        );
    }
}
