<?php

namespace Opencast\Routes\Tags;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\Playlists;
use Opencast\Models\Videos;
use Opencast\Models\Tags;

class TagListForUser extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $user;

        $tags = Tags::findByUser_id($user->id);

        $ret = [];

        foreach ($tags as $tag) {
            $ret[] = $tag->toArray();
        }

        return $this->createResponse($ret, $response->withStatus(200));
    }
}
