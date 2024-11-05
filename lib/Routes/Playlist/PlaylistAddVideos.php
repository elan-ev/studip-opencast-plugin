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

class PlaylistAddVideos extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $user;

        $playlist = Playlists::findOneByToken($args['token']);

        $data = $this->getRequestData($request);
        $video_tokens = $data['videos'];
        $course_id    = $data['course_id'];

        $videos = array_map(function ($token) {
            return Videos::findOneByToken($token);
        }, $video_tokens);

        if (!PlaylistMigration::isConverted()) {
            foreach ($videos as $video) {
                $plvideo = new PlaylistVideos;
                $plvideo->setData([
                    'playlist_id' => $playlist->id,
                    'video_id'    => $video->id
                ]);

                try {
                    $playlist->videos[] = $plvideo;
                } catch (\InvalidArgumentException $e) {
                }
            }

            $playlist->videos->store();
            return $response->withStatus(204);
        }

        // Get playlist entries from Opencast
        $playlist_client = ApiPlaylistsClient::getInstance($playlist->config_id);
        $oc_playlist = $playlist_client->getPlaylist($playlist->service_playlist_id);

        if (!$oc_playlist) {
            // something went wrong with playlist creation, try again
            $oc_playlist = $playlist_client->createPlaylist([
                'title'                => $playlist['title'],
                'description'          => $playlist['description'],
                'creator'              => $playlist['creator'],
                'accessControlEntries' => []
            ]);

            if (!$oc_playlist) {
                throw new Error(_('Wiedergabeliste kontte nicht zu Opencast hinzugefügt werden!'), 500);
            }

            $playlist->service_playlist_id = $oc_playlist->id;
            $playlist->store();
        }

        $entries = $oc_playlist->entries;

        foreach ($videos as $video) {
            // check what permissions the current user has on the playlist and video
            if (!Authority::canAddVideoToPlaylist($user, $playlist, $video, $course_id)) {
                throw new \AccessDeniedException();
            }

            if (!$video->episode) continue;

            $entries[] = [
                'contentId' => $video->episode,
                'type' => 'EVENT'
            ];

            // Only add video if not contained in entries
            $entry_exists = current(array_filter($entries, function($e) use ($video) {
                return $e->contentId === $video->episode;
            }));

            if (!$entry_exists) {
                $entries[] = [
                    'contentId' => $video->episode,
                    'type' => 'EVENT'
                ];
            }
        }

        // Update videos in playlist of Opencast
        $oc_playlist = $playlist_client->updateEntries($oc_playlist->id, $entries);
        if (!$oc_playlist) {
            throw new Error(_('Die Videos konnten nicht hinzugefügt werden.'), 500);
        }

        // Update playlist videos in DB
        $playlist->setEntries($oc_playlist->entries);

        return $response->withStatus(204);
    }
}