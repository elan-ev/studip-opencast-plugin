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
            $ret[] = $video->toSanitizedArray();
        }

        return $this->createResponse([
            'videos' => $ret,
            'count'  => $videos['count'],
            'sql'    => $videos['sql']
        ], $response);
    }
}
