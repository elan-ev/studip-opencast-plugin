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
use Opencast\Models\Workflow;
use Opencast\Models\WorkflowConfig;
use Opencast\Models\LTI\LtiHelper;

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
            $config_list[$conf->id] = [
                'id'         => $conf->id,
                'name'       => $conf->service_url,
                'version'    => $conf->service_version,
                'ingest'     => reset(Endpoints::findBySql("config_id = ? AND service_type = 'ingest'", [$conf->id]))->service_url,
                'apievents'  => reset(Endpoints::findBySql("config_id = ? AND service_type = 'apievents'", [$conf->id]))->service_url,
                'studio'     => $conf->service_url . '/studio/index.html',
                'lti_num'    => sizeof(LtiHelper::getLtiLinks($conf->id))              // used to iterate over all Opencast nodes
            ];
        }

        $workflows = new \SimpleCollection(Workflow::findBySql('1'));
        $workflow_configs = new \SimpleCollection(WorkflowConfig::findBySql('1'));

        return $this->createResponse([
            'server'    => $config_list ?: [],
            'settings'  => $this->getGlobalConfig(),
            'workflows' => $workflows->toArray(),
            'workflow_configs' => $workflow_configs->toArray()
        ], $response);
    }

    private function getGlobalConfig()
    {
        $config = [];

        foreach ($this->container['opencast']['global_config_options'] as $option) {
            if (in_array($option, self::BLACKLISTED_CONFIG_OPTIONS) !== false) {
                continue;
            }

            $config[$option] = \Config::get()->$option;
        }

        return $config;
    }
}
