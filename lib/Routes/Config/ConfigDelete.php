<?php

namespace Opencast\Routes\Config;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\Config;

class ConfigDelete extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        $config = Config::where('id', $args['id'])->first();
        if ($config == null)
        {
            throw new Error('config not found.', 404);
        }

        if (!$config->delete()) {
            throw new Error('Could not delete config.', 500);
        }

        return $response->withStatus(204);
    }
}
