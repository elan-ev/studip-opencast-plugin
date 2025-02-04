<?php

namespace Opencast\Routes\Playlist;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\REST\ApiPlaylistsClient;
use Opencast\Models\Playlists;
use Opencast\Models\Videos;
use Opencast\Models\PlaylistVideos;
use Opencast\Helpers\PlaylistMigration;

class PlaylistRemoveVideos extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $user;

        $playlist = Playlists::findOneByToken($args['token']);

        $data = $this->getRequestData($request);
        $video_tokens = $data['videos'];
        $course_id    = $data['course_id'];

        $videos = array_map(function ($token) { return Videos::findOneByToken($token); }, $video_tokens);

        if (!PlaylistMigration::isConverted()) {
            foreach ($videos as $video) {

                $plvideo = PlaylistVideos::findOneBySQL(
                    'playlist_id = :playlist_id AND video_id = :video_id',
                    [
                        'playlist_id' => $playlist->id,
                        'video_id'    => $video->id
                    ]
                );
                $plvideo->delete();
            }

            Videos::checkEventACL(null, null, $video);

            return $response->withStatus(204);
        }

        // Get playlist entries from Opencast
        $playlist_client = ApiPlaylistsClient::getInstance($playlist->config_id);
        $oc_playlist = $playlist_client->getPlaylist($playlist->service_playlist_id);

        $old_entries = (array)$oc_playlist->entries;
        $entries = (array)$oc_playlist->entries;

        foreach ($videos as $video) {
            if (!Authority::canAddVideoToPlaylist($user, $playlist, $video, $course_id)) {
                throw new \AccessDeniedException();
            }

            // Prevent removing video from playlist when it is livestream.
            if ((bool) $video->is_livestream) {
                return $this->createResponse([
                    'message' => [
                        'type' => 'error',
                        'text' => _('Entfernung eines Livestream-Videos aus der Wiedergabeliste ist nicht erlaubt.')
                    ],
                ], $response->withStatus(403));
            }

            // Remove all occurrences of video from entries
            $entries = array_values(array_filter($entries, function ($entry) use ($video) {
                return $entry->contentId !== $video->episode;
            }));
        }

        if (count($entries) < count($old_entries)) {
            // Remove videos in playlist of Opencast
            $oc_playlist = $playlist_client->updateEntries($oc_playlist->id, $entries);
            if (!$oc_playlist) {
                throw new Error(_('Die Videos konnten nicht entfernt werden.'), 500);
            }
        }

        // Update playlist videos in DB
        $playlist->setEntries((array)$oc_playlist->entries);

        return $response->withStatus(204);
    }
}
