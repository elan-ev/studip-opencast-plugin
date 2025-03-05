<?php

namespace Opencast\Routes\Courseware;

use Opencast\Errors\AuthorizationFailedException;
use Opencast\Models\Filter;
use Opencast\Models\Videos;
use Opencast\OpencastController;
use Opencast\OpencastTrait;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CoursewareVideoList extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        $params = $request->getQueryParams();

        // select all videos the current user has perms on
        $videos = Videos::getCoursewareVideos(new Filter($params));

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
