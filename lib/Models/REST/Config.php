<?php

namespace Opencast\Models\REST;

use Opencast\Models\Config as ConfigModel;

class Config
{
    const SERVICES = [
        'org.opencastproject.external.events'              => 'apievents',
        'org.opencastproject.external'                     => 'apiseries',
        'org.opencastproject.external.workflows.instances' => 'apiworkflows',
        'org.opencastproject.external.playlists'           => 'apiplaylists',
        'org.opencastproject.capture.admin'                => 'capture-admin',
        'org.opencastproject.ingest'                       => 'ingest',
        'org.opencastproject.scheduler'                    => 'recordings',
        'org.opencastproject.engage.ui.player.redirect'    => 'play',                 // ausser hier: engage-node
        'org.opencastproject.search'                       => 'search',               // ausser hier: engage-node
        'org.opencastproject.series'                       => 'series',
        'org.opencastproject.serviceregistry'              => 'services',
        'org.opencastproject.fileupload'                   => 'upload',
        'org.opencastproject.workflow'                     => 'workflow',
    ];

    const ENGAGE_NODE_SERVICE_TYPES = [
        'play',
        'search',
    ];

    /**
     * Get the connected opencast instance version
     *
     * @param string $config_id the config id
     *
     * @return string|boolean the opencast version, or false if unable to get
     */
    public static function getOCBaseVersion($config_id)
    {
        $config = ConfigModel::getBaseServerConf($config_id);

        // populate config_id if calling the RestClient directly
        $config['config_id'] = $config['id'];
        $oc = new RestClient($config);

        $response = $oc->opencastApi->sysinfo->getVersion('opencast');
        if ($response['code'] == 200) {
            if (isset($response['body']->version)) {
                return $response['body']->version;
            } else if (is_array($response['body']->versions)) {
                return array_reduce($response['body']->versions, function($carry, $item) {
                    if (empty($carry)) {
                        $carry = $item->version;
                    } else {
                        $carry .= ', ' . $item->version;
                    }
                    return $carry;
                });
            }
        }

        return false;
    }

    public static function retrieveRESTservices($components, $service_url)
    {
        $oc_services = self::SERVICES;
        $services   = [];

        foreach ($components as $service) {
            if (($service_url['host'] == "localhost" || !preg_match('#https?://localhost.*#', $service->host))
                && mb_strpos($service->host, $service_url['scheme']) === 0
            ) {
                // check if service is wanted, active, online and not in maintenance
                if (isset($oc_services[$service->type])
                    && $service->online && $service->active && !$service->maintenance
                ) {
                    // TODO: check duplicate entries for same service-type
                    $services[$service->host . $service->path]
                        = $oc_services[$service->type];
                }

                if ($service->path == '/admin-ng/event') {
                    $services[$service->host . $service->path]
                        = 'adming-ngevent';
                }
            }
        }

        return $services;
    }

    /**
     * Checks if the Opencast API is reachable for the given configuration.
     *
     * This method performs a basic GET request to the Opencast API base endpoint
     * using the provided configuration ID. It returns true if the API responds
     * with a 200 HTTP status code, indicating that the Opencast instance is reachable.
     *
     * @param int $config_id The ID of the Opencast configuration to check.
     * @param bool $check_engage_node A flag to determine whether the check should be performed against engage node (e.g play or search endpoint)
     * @return bool True if the Opencast API is reachable, false otherwise.
     */
    public static function checkOpencastAPIConnectivity(int $config_id, bool $check_engage_node = false): bool
    {
        $success = false;
        $config = null;
        if ($check_engage_node) {
            $engage_related_service = 'search';
            $config = ConfigModel::getConfigForService($engage_related_service, $config_id);
            // Since the endpoint url by default has an ending of service label, we make sure it is removed!
            if (!empty($config['service_url'])) {
                $config['service_url'] = rtrim(str_replace($engage_related_service, '', $config['service_url']), '/');
            }
        } else {
            $config = ConfigModel::getBaseServerConf($config_id);
        }

        if (empty($config)) {
            return false;
        }

        // populate config_id if calling the RestClient directly
        $config['config_id'] = $config['id'];
        $oc = new RestClient($config);

        $response = $oc->opencastApi->baseApi->get();

        return ($response['code'] == 200);
    }
}
