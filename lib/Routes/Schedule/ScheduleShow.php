<?php

namespace Opencast\Routes\Schedule;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Providers\Perm;

class ScheduleShow extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        // TODO: This needs to be filled up with course id and user id!
        if (!Perm::schedulingAllowed()) {
            throw new \AccessDeniedException();
        }
        // TODO: Fill this up when necessary!
        return $response->withStatus(200);
    }
}
