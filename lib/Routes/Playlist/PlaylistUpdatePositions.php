<?php

namespace Opencast\Routes\Playlist;

use Opencast\Models\Videos;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\Playlists;
use Opencast\Models\PlaylistVideos;
use Opencast\Models\REST\ApiPlaylistsClient;
use Opencast\Helpers\PlaylistMigration;

class PlaylistUpdatePositions extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        $playlist = Playlists::findOneByToken($args['token']);

        // check what permissions the current user has on the playlist
        $uperm = $playlist->getUserPerm();

        if (empty($uperm) || ($uperm != 'owner' && $uperm != 'write'))
        {
            throw new \AccessDeniedException();
        }

        $ordered_tokens = $this->getRequestData($request);

        $ordered_entries = [];
        foreach ($ordered_tokens as $token) {
            $playlist_video = Videos::findOneBySQL("INNER JOIN oc_playlist_video ON
                (oc_playlist_video.video_id = oc_video.id AND oc_playlist_video.playlist_id = ?)
                WHERE oc_video.token = ?", [$playlist->id, $token]);

            // Ensure playlist contains video
            if (empty($playlist_video)) {
                throw new \AccessDeniedException(_('Die zu sortierenden Videos sind nicht in der Wiedergabeliste enthalten.'));
            }

            $ordered_entries[] = [
                'contentId' => $playlist_video->episode,
                'type' => 'EVENT'
            ];
        }

        // Update entries in Opencast
        if (PlaylistMigration::isConverted()) {
            $playlist_client = ApiPlaylistsClient::getInstance($playlist->config_id);
            $oc_playlist = $playlist_client->updateEntries($playlist->service_playlist_id, $ordered_entries);
            if (!$oc_playlist) {
                throw new Error(_('Die Videos in der Wiedergabelisten konnten nicht sortiert werden.'), 500);
            }
        }

        PlaylistVideos::reorder($playlist->token, $ordered_tokens);

        return $response->withStatus(200);
    }
}
