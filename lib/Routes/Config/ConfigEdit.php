<?php

namespace Opencast\Routes\Config;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\Config;

class ConfigEdit extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        $json = $this->getRequestData($request);

        $config = Config::where('id', $args['id'])->first();

        foreach ($json['config'] as $attr => $val) {
            if (isset($config->$attr)) {
                $config->$attr = $val;
            }
        }

        $config->save();

        return $response->withStatus(204);
    }
}
