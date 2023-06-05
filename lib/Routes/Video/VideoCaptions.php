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

class VideoCaptions extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        $token = $args['token'];
        $captions = Videos::getCaptionByToken($token);

        return $this->createResponse([
            'caption' => $captions
        ], $response);
    }
}
