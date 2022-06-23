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
            $ret_config = $conf->toArray();
            $ret_config = array_merge($ret_config, $ret_config['settings']);
            unset($ret_config['settings']);
            $config_list[] = $ret_config;
        }

        $languages = [];
        foreach ($GLOBALS['CONTENT_LANGUAGES'] as $id => $lang) {
            $languages[$id] = array_merge(['id' => $id], $lang);
        }

        return $this->createResponse([
            'server'    => $config_list ?: [],
            'settings'  => $this->getGlobalConfig(),
            'languages' => $languages
        ], $response);
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
