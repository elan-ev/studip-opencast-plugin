<?php

namespace Opencast\Routes\Video;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;

class VideoList extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        $array = [
            (object) ["id" => 1, "token" => 2, "title" => "test1"],
            (object) ["id" => 2, "token" => 3, "title" => "test1"]
        ];
        return $this->createResponse($array, $response);
    }
}
