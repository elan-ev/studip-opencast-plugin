<?php

namespace Opencast\Routes\Config;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\Config;

class ConfigUpdate extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        $constants = $this->container->get('opencast');
        $json = $this->getRequestData($request);

        foreach ($json['settings'] as $config) {
            // validate values
            if (in_array($config['name'], $constants['global_config_options'])) {
                \Config::get()->store($config['name'], $config['value']);
            }
        }

        return $response->withStatus(204);
    }
}
