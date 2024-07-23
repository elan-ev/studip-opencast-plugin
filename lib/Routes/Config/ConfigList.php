<?php

namespace Opencast\Routes\Config;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\Config;
use Opencast\Models\Endpoints;
use Opencast\Models\Resources;
use Opencast\Models\ScheduleHelper;
use Opencast\Models\REST\Config as OCConfig;
use Opencast\Helpers\PlaylistMigration;

class ConfigList extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        $config = Config::findBySql(1);

        $config_list = [];

        foreach ($config as $conf) {
            $ret_config = $conf->toArray();
            $ret_config['active'] = filter_var(
                $ret_config['active'],
                FILTER_VALIDATE_BOOLEAN
            );
            $ret_config = array_merge($ret_config, $ret_config['settings']);
            unset($ret_config['settings']);
            $config_list[] = $ret_config;
        }

        $languages = [];
        foreach ($GLOBALS['CONTENT_LANGUAGES'] as $id => $lang) {
            $languages[$id] = array_merge(['id' => $id], $lang);
        }

        $response_data = [
            'server'    => $config_list ?: [],
            'settings'  => $this->getGlobalConfig(),
            'languages' => $languages
        ];

        // Checker to provide recourses.
        $resources = Resources::getStudipResources();
        if (!empty(Endpoints::getEndpoints()) && !empty($resources)) {
            $response_data['scheduling'] = ScheduleHelper::prepareSchedulingConfig($config_list, $resources);
        }

        if (!PlaylistMigration::isConverted() && count($config_list) &&
            version_compare(
                OCConfig::getOCBaseVersion(\Config::get()->OPENCAST_DEFAULT_SERVER),
                '16',
                '>='
            )
        ) {
            $response_data['can_migrate_playlists'] = true;
        }

        return $this->createResponse($response_data, $response);
    }

    private function getGlobalConfig()
    {
        $config = [];

        foreach ($this->container->get('opencast')['global_config_options'] as $option) {
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
