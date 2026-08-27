<?php

namespace Opencast\Routes\Playlist;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\Filter;
use Opencast\Models\Playlists;
use Opencast\Models\Videos;
use Opencast\Models\PlaylistSeminarVideos;
use Opencast\Caching\VideosCaching;

class PlaylistVideoList extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $perm;

        $params = $request->getQueryParams();
        $course_id = isset($params['cid']) ? $params['cid'] : null;

        // first, check if user has access to this playlist
        $playlist = Playlists::findOneByToken($args['token']);

        if (!$playlist) {
            throw new \AccessDeniedException();
        }

        // check if playlist is connected to the passed course and user is part of that course as well
        $permission = false;
        if ($course_id && !empty($playlist->courses)) {
            $playlist_connected_courses_ids = array_column($playlist->courses->toArray(), 'id');
            if ($perm->have_studip_perm('user', $course_id) && in_array($course_id, $playlist_connected_courses_ids)) {
                $permission = true;
            }
        }

        if (!$course_id || !$permission) {
            // check what permissions the current user has on the playlist
            $uperm = $playlist->getUserPerm();

            if (empty($uperm) || !$uperm) {
                throw new \AccessDeniedException();
            }
        }

        $response_result = [
            'videos' => [],
            'count'  => 0,
        ];

        $filter = new Filter($params);
        $video_caching = new VideosCaching();
        $playlist_videos_cache = $video_caching->playlistVideos($playlist->id);
        $unique_query_id = $filter->decodeVars();
        $response_result = $playlist_videos_cache->read($unique_query_id);
        if (empty($response_result)) {
            // show videos for this playlist and filter them with optional additional filters
            $videos = Videos::getPlaylistVideos($playlist->id, $filter);

            $ret = [];
            foreach ($videos['videos'] as $video) {
                $video_array = $video->toSanitizedArray($course_id, $playlist->id);
                if (!empty($video_array['perm']) && ($video_array['perm'] == 'owner' || $video_array['perm'] == 'write'))
                {
                    $video_array['perms'] = $video->perms->toSanitizedArray();
                }
                $ret[] = $video_array;
            }

            $response_result = [
                'videos' => $ret,
                'count'  => $videos['count'],
            ];

            $playlist_videos_cache->write($unique_query_id, $response_result);
        }

        return $this->createResponse($response_result, $response);
    }
}
