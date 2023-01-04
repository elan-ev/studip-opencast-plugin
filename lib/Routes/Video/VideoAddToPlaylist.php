<?php

namespace Opencast\Routes\Video;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\Videos;
use Opencast\Models\PlaylistVideos;
use Opencast\Models\Playlists;

class VideoAddToPlaylist extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $perm;

        $token = $args['token'];
        $video = Videos::findByToken($token);

        if (empty($video)) {
            throw new Error(_('Das Video kann nicht gefunden werden'), 404);
        }

        $user_perm = $video->getUserPerm();
        if (empty($user_perm) ||
            ($user_perm != 'owner' && $user_perm != 'write'))
        {
            throw new \AccessDeniedException();
        }

        $json = $this->getRequestData($request);
        $playlists = $json['playlists'];

        try {
            // Clear all existing record for this video.
            PlaylistVideos::deleteBySql(
                'video_id = ?', [$video->id]
            );

            // Add record to the VideoSeminars based on courses
            if (!empty($playlists)) {
                foreach ($playlists as $playlist) {
                    // check if user has perms on the playlist
                    $db_playlist = reset(Playlists::findByToken($playlist['token']));
                    if (in_array($db_playlist->getUserPerm(), ['owner', 'write']) === true) {
                        $pv = new PlaylistVideos();
                        $pv->video_id = $video->id;
                        $pv->playlist_id = $db_playlist->id;
                        $pv->store();
                    }
                }
            }

            $message = [
                'type' => 'success',
                'text' => _('Die Wiedergabelisten-Verknüpfungen des Videos wurden aktualisiert.')
            ];
        } catch (\Throwable $th) {
            $message = [
                'type' => 'error',
                'text' => _('Die Wiedergabelisten-Verknüpfungen des Videos konnten nicht aktualisiert werden!') . $th->getMessage()
            ];
        }

        return $this->createResponse([
            'message' => $message,
        ], $response->withStatus(200));
    }
}
