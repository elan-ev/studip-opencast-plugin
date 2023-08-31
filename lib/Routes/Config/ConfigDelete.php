<?php

namespace Opencast\Routes\Config;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\Config;
use Opencast\Models\Videos;
use Opencast\Models\Helpers;

class ConfigDelete extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        $config = Config::find($args['id']);

        if ($config == null)
        {
            throw new Error('config not found.', 404);
        }

        // delete all videos for this config
        Videos::deleteByConfig_id($config->id);

        if (!$config->delete()) {
            throw new Error('Could not delete config.', 500);
        }
        
        // Validate that a correct default server is set
        Helpers::validateDefaultServer();

        return $response->withStatus(204);
    }
}
