<?php

namespace Opencast\Routes\Playlist;

use Opencast\Models\Playlists;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\PlaylistSeminars;

/**
 * Copy a playlist into a course or user contents
 */
class PlaylistCopy extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $perm;

        $json = $this->getRequestData($request);

        $source_playlist = Playlists::findOneByToken($args['token']);
        $destination_course = $json['course'];

        // Check playlist permissions
        $perm_playlist = $source_playlist->getUserPerm();
        if (empty($perm_playlist) || !$perm_playlist)
        {
            throw new \AccessDeniedException();
        }

        // Check permission on course destination
        if (!empty($destination_course)) {
            if (!$perm->have_studip_perm('tutor', $destination_course)) {
                throw new \AccessDeniedException();
            }
        }

        try {
            // Copy playlist
            $new_playlist = $source_playlist->copy();

            // Link new playlist to course
            if (!empty($destination_course)) {
                PlaylistSeminars::create([
                    'playlist_id' => $new_playlist->id,
                    'seminar_id'  => $destination_course,
                    'visibility'  => 'visible'
                ]);
            }

            $message = [
                'type' => 'success',
                'text' => _('Die Kopiervorgänge wurden ausgeführt.')
            ];
        } catch (\Throwable $e) {

            $message = [
                'type' => 'error',
                'text' => _('Die Kopiervorgänge konnten nicht abgeschlossen werden!')
            ];
        }

        return $this->createResponse([
            'message' => $message
        ], $response->withStatus(200));
    }
}
