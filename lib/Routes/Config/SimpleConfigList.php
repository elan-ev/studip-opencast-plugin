<?php

namespace Opencast\Routes\Config;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\Config;
use Opencast\Models\Endpoints;

class SimpleConfigList extends OpencastController
{
    use OpencastTrait;

    private const BLACKLISTED_CONFIG_OPTIONS = [
        'OPENCAST_RESOURCE_PROPERTY_ID',
        'OPENCAST_API_TOKEN'
    ];

    public function __invoke(Request $request, Response $response, $args)
    {
        $config = Config::findBySql(1);

        $config_list = [];

        foreach ($config as $conf) {
            $config_list[] = [
                'id'      => $conf->id,
                'name'    => $conf->service_url,
                'version' => $conf->service_version,
                'ingest'  => reset(Endpoints::findBySql("config_id = ? AND service_type = 'ingest'", [$conf->id]))->service_url
            ];
        }

        return $this->createResponse([
            'server'    => $config_list ?: [],
            'settings'  => $this->getGlobalConfig()
        ], $response);
    }

    private function getGlobalConfig()
    {
        $config = [];

        foreach ($this->container['opencast']['global_config_options'] as $option) {
            if (in_array($option, self::BLACKLISTED_CONFIG_OPTIONS) !== false) {
                continue;
            }

            $config[] = [
                'name'        => $option,
                'value'       => \Config::get()->$option,
            ];
        }

        return $config;
    }
}
