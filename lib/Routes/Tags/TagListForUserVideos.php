<?php

namespace Opencast\Routes\Tags;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\Tags;

class TagListForUserVideos extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        $ret = Tags::getUserVideosTags();

        return $this->createResponse($ret, $response->withStatus(200));
    }
}
