<?php

namespace Opencast\Routes\Log;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\Config;

class LogEntryCreate extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        $json = $this->getRequestData($request);


        // TODO: create correct log entry

        return $response->withStatus(204);
    }
}
