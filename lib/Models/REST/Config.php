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
                // check if service is wanted, active and online
                if (isset($oc_services[$service->type])
                    && $service->online && $service->active
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
}
