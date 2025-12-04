<?php

namespace Opencast\Routes\Video;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\Filter;
use Opencast\Models\Videos;
use Opencast\Caching\VideosCaching;

class VideoList extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $user;
        $params = $request->getQueryParams();

        $response_result = [
            'videos' => [],
            'count'  => 0,
        ];

        $filter = new Filter($params);
        $video_caching = new VideosCaching();
        $user_videos_cache = $video_caching->userVideos($user->id);
        $unique_query_id = $filter->decodeVars();
        $response_result = $user_videos_cache->read($unique_query_id);
        if (empty($response_result)) {
            // select all videos the current user has perms on
            $videos = Videos::getUserVideos($filter);

            $ret = [];
            foreach ($videos['videos'] as $video) {
                $video_array = $video->toSanitizedArray();
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

            $user_videos_cache->write($unique_query_id, $response_result);
        }

        return $this->createResponse($response_result, $response);
    }
}
