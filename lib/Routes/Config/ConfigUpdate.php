<?php

namespace Opencast\Routes\Config;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\ScheduleHelper;
use Opencast\Models\Helpers;
use Opencast\Models\Config;
use Opencast\Models\WorkflowConfig;

use Opencast\Models\I18N as _;

class ConfigUpdate extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        $constants = $this->container->get('opencast');
        $json = $this->getRequestData($request);

        // load oc server configs
        $config = new \SimpleCollection(Config::findBySql(1));

        // Storing General Configs.
        foreach ($json['settings'] as $config) {
            if (in_array($config['name'], array_map(function($e) { return $e['name']; }, $constants['global_config_options']))) {
                \Config::get()->store($config['name'], $config['value']);
            }
        }
        // Validate that a correct default server is set
        Helpers::validateDefaultServer();

        // Storing Resources Configs.
        $messages = [];
        if (isset($json['resources']) && !empty($json['resources'])) {
            foreach ($json['resources'] as $resource) {
                if (!empty($resource['capture_agent'])) {
                    try {
                        ScheduleHelper::addUpdateResource($resource['id'], $resource['config_id'], $resource['capture_agent'], $resource['workflow_id'], $resource['livestream_workflow_id']);
                    } catch (\Throwable $th) {
                        $messages[] = [
                            'type' => 'error',
                            'text' => sprintf(_('Capture Agent für (%s) konnte nicht gespeichert werden!'), "{$resource['name']}")
                        ];
                    }
                } else {
                    // ScheduleHelper::deleteResource($resource['id']);
                }
            }
        }

        if (isset($json['workflow_configs']) && !empty($json['workflow_configs'])) {
            foreach ($json['workflow_configs'] as $wf_config) {
                $db_wf_conf = WorkflowConfig::findOneBySql("id = ?", [$wf_config['id']]);
                $db_wf_conf->setValue('workflow_id', $wf_config['workflow_id']);
                $db_wf_conf->store();
            }
        }

        if (empty($messages)) {
            $messages[] = [
                'type' => 'success',
                'text' => _('Einstellungen gespeichert')
            ];
        }

        return $this->createResponse([
            'messages'=> $messages,
        ], $response);
    }
}
