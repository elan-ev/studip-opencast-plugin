<?php

namespace Opencast\Routes\Config;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\Config;

class ConfigShow extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        $config = Config::find($args['id']);

        if ($config) {
            $config->config['id'] = $config->id;
            return $this->createResponse(['config' => json_decode($config->config)], $response);
        }

        throw new Error('Config not found', 404);
    }
}
