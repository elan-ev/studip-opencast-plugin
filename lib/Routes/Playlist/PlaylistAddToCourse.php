<?php

namespace Opencast\Routes\Playlist;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\Playlists;
use Opencast\Models\PlaylistSeminars;

class PlaylistAddToCourse extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $perm;

        $token = $args['token'];

        $playlist = Playlists::findOneByToken($args['token']);

        if (empty($playlist)) {
            throw new Error(_('Die Wiedergabeliste kann nicht gefunden werden'), 404);
        }

        if (empty($playlist->getUserPerm())) {
            throw new \AccessDeniedException();
        }

        $json = $this->getRequestData($request);
        $courses = $json['courses'];

        try {
            // Clear all existing record for this playlist.
            PlaylistSeminars::deleteBySql(
                'playlist_id = ?', [$playlist->id]
            );

            // Add record to the PlaylistSeminars based on courses
            if (!empty($courses)) {
                foreach ($courses as $course) {
                    if ($perm->have_studip_perm('tutor', $course['id'])) {
                        $playlist_seminar = new PlaylistSeminars;
                        $playlist_seminar->playlist_id = $playlist->id;
                        $playlist_seminar->seminar_id = $course['id'];
                        $playlist_seminar->visibility = 'visible';
                        $playlist_seminar->store();
                    }
                }
            }

            $message = [
                'type' => 'success',
                'text' => _('Die Kurs-Verknüpfungen der Wiedergabeliste wurden aktualisiert.')
            ];
        } catch (\Throwable $th) {
            var_dump($th);die;
            $message = [
                'type' => 'error',
                'text' => _('Die Kurs-Verknüpfungen der Wiedergabeliste konnten nicht aktualisiert werden!')
            ];
        }

        return $this->createResponse([
            'message' => $message,
        ], $response->withStatus(200));
    }
}
