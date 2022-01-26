<?php

namespace Opencast\Routes\Config;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\Config;

class ConfigList extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        $config = Config::findBySql(1);

        $config_list = [];

        foreach ($config as $conf) {
            $config_list[] = $conf->toArray();
        }

        if (!empty($config)) {
            return $this->createResponse([
                'server'   => $config_list,
                'settings' => $this->getGlobalConfig()
            ], $response);
        }

        return $this->createResponse([], $response);
    }

    private function getGlobalConfig()
    {
        $config = [];

        foreach ($this->container['opencast']['global_config_options'] as $option) {
            $data = \Config::get()->getMetadata($option);
            $config[] = [
                'name'        => $option,
                'description' => $data['description'],
                'value'       => \Config::get()->$option,
                'type'        => $data['type']
            ];
        }

        return $config;
    }
}
