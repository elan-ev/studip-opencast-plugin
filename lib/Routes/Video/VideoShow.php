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

class VideoShow extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        // select all videos the current user has perms on
        $video = Videos::findByToken($args['token']);

        // if no perms are found only return the token back with the config_id the video resides on,
        // so the courseware-block can do its magic
        if (empty($video) || !$video->havePerm('read')) {
            return $this->createResponse([
                'video' => [
                    'token'     => $video->token,
                    'config_id' => $video->config_id
                ]
            ], $response);
        }

        $video_array = $video->toSanitizedArray();
        if (!empty($video_array['perm']) && ($video_array['perm'] == 'owner' || $video_array['perm'] == 'write'))
        {
            $video_array['perms'] = $video->perms->toSanitizedArray();
        }

        return $this->createResponse([
            'video' => $video_array
        ], $response);
    }
}
