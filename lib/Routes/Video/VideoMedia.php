<?php

namespace Opencast\Routes\Video;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\Videos;
use Opencast\Models\REST\ApiEventsClient;


class VideoMedia extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        // select all videos the current user has perms on
        $video = Videos::findByToken($args['token']);

        if (empty($video)) {
            throw new Error(_('Das Video kann nicht gefunden werden'), 404);
        }

        if (!$video->havePerm('write'))
        {
            throw new \AccessDeniedException();
        }

        $api_client = ApiEventsClient::getInstance($video->config_id);
        $media = $api_client->getMedia($video->episode);

        return $this->createResponse([
            'media' => $media
        ], $response);
    }
}
