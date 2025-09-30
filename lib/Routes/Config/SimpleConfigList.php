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
        global $user;

        // Only show active servers
        $config = Config::findBySql('active = 1');

        $config_list = [];

        foreach ($config as $conf) {
            $config_list[$conf->id] = [
                'id'                => $conf->id,
                'name'              => $conf->service_url,
                'version'           => $conf->service_version,
                'timeout'           => (int) $conf->timeout_ms ?? 0,
                'connect_timeout'   => (int) $conf->connect_timeout_ms ?? 0,
                'play'              => $this->getServiceUrl('play', $conf->id),
                'ingest'            => $this->getServiceUrl('ingest', $conf->id),
                'apievents'         => $this->getServiceUrl('apievents', $conf->id),
                'apiplaylists'      => $this->getServiceUrl('apiplaylists', $conf->id),
                'apiworkflows'      => $this->getServiceUrl('apiworkflows', $conf->id),
                'studio'            => $conf->service_url . '/studio/index.html',
                'lti_num'           => sizeof(LtiHelper::getLtiLinks($conf->id)), // used to iterate over all Opencast nodes
                'allow_upload_wf_cp' => (bool) $conf->settings['allow_upload_wf_cp'] ?? false,
            ];
        }

        $workflows = new \SimpleCollection(Workflow::findBySql('1'));
        $workflow_configs = new \SimpleCollection(WorkflowConfig::findBySql('1'));

        return $this->createResponse([
            'server'                    => $config_list ?: [],
            'settings'                  => $this->getGlobalConfig(),
            'workflows'                 => $workflows->toArray(),
            'workflow_configs'          => $workflow_configs->toArray(),
            'plugin_assets_url'         => \PluginEngine::getPlugin('OpencastV3')->getAssetsUrl(),
            'auth_url'                  => \PluginEngine::getURL('opencastv3', [], 'redirect/authenticate', true),
            'redirect_url'              => \PluginEngine::getURL('opencastv3', [], 'redirect/perform', true),
            'course_id'                 => \Context::getId() ?: null,
            'user_language'             => getUserLanguage($user->id),
            'default_upload_file_types' => Workflow::DEFAULT_UPLOAD_FILE_TYPES,
        ], $response);
    }

    private function getGlobalConfig()
    {
        $config = [];

        foreach ($this->container->get('opencast')['global_config_options'] as $option) {
            if (in_array($option['name'], self::BLACKLISTED_CONFIG_OPTIONS) !== false) {
                continue;
            }

            $config[$option['name']] = \Config::get()->{$option['name']};
        }

        return $config;
    }

    /**
     * Retrieves the service URL for a given service type and configuration ID.
     *
     * @param string $service_type The type of service endpoint (e.g., 'play', 'ingest').
     * @param int $config_id The ID of the Opencast configuration.
     * @return string|null The service URL if found, or null if not available.
     */
    private function getServiceUrl($service_type, $config_id)
    {
        $service_url = null;
        $endpoint_records = Endpoints::findBySql("config_id = ? AND service_type = ?", [$config_id, $service_type]);
        if (!empty($endpoint_records)) {
            $service_url = reset($endpoint_records)->service_url;
        }
        return $service_url;
    }
}
