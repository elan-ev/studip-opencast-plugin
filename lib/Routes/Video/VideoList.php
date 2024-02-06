<?php

namespace Opencast\Routes\Video;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\Filter;
use Opencast\Models\Videos;

class VideoList extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        $params = $request->getQueryParams();

        // select all videos the current user has perms on
        $videos = Videos::getUserVideos(new Filter($params));

        $ret = [];
        foreach ($videos['videos'] as $video) {
            $video_array = $video->toSanitizedArray();
            if (!empty($video_array['perm']) && ($video_array['perm'] == 'owner' || $video_array['perm'] == 'write'))
            {
                $video_array['perms'] = $video->perms->toSanitizedArray();
            }
            $ret[] = $video_array;
        }

        return $this->createResponse([
            'videos' => $ret,
            'count'  => $videos['count'],
        ], $response);
    }
}
