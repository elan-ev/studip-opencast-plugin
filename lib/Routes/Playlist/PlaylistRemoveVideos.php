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

        /* Permission check */
        foreach ($videos as $video) {
            // check what permissions the current user has on the playlist and video
            if (!Authority::canAddAndRemoveVideoInPlaylist($user, $playlist, $video, $course_id)) {
                throw new \AccessDeniedException();
            }
        }

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

        $playlist->removeEntries($videos);

        return $response->withStatus(204);
    }
}
