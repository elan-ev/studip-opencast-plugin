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

        $playlist_videos = PlaylistVideos::findBySql(
            'video_id = ?', [$video->id]
        );

        try {
            // First we look for removable or already saved playlists
            foreach($playlist_videos as $playlist_video) {
                $db_playlist = Playlists::findOneById($playlist_video->playlist_id);

                // Look for a corrosponding playlist from playlists in request
                $playlist_key = null;
                foreach ($playlists as $key => $playlist) {
                    if ($playlist['token'] === $db_playlist->token) {
                        $playlist_key = $key;
                        break;
                    }
                }
                
                // check if user has perms on the playlist
                if (in_array($db_playlist->getUserPerm(), ['owner', 'write']) === true) {
                    // Playlist was removed, so we remove it
                    if (is_null($playlist_key)) {
                        $playlist_video->delete();
                    }
                    // else Playlist was added or is still active, nothing to do
                }

                if (!is_null($playlist_key)) {
                    unset($playlists[$playlist_key]);
                }
            }

            // Add videos to remaining playlists
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

            $message = [
                'type' => 'success',
                'text' => _('Das Video wurde der Wiedergabeliste hinzugefügt.')
            ];
        } catch (\Throwable $th) {
            $message = [
                'type' => 'error',
                'text' => _('Das Video konnte der Wiedergabeliste nicht hinzugefügt werden!') . $th->getMessage()
            ];
        }

        return $this->createResponse([
            'message' => $message,
        ], $response->withStatus(200));
    }
}
