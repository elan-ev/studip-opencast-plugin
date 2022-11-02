<?php

namespace Opencast\Providers;

class OpencastConstants implements \Pimple\ServiceProviderInterface
{
    /**
     * Diese Methode wird automatisch aufgerufen, wenn diese Klasse dem
     * Dependency Container der Slim-Applikation hinzugefügt wird.
     *
     * @param \Pimple\Container $container der Dependency Container
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function register(\Pimple\Container $container)
    {
        $container['opencast'] = [
            'services' => [
                'apievents',            // alles admin-node
                'apiseries',
                'apiworkflows',
                'capture-admin',
                'ingest',
                'recordings',
                'search',               // ausser hier: engage-node
                'series',
                'services',
                'upload',
                'workflow'
            ],
            'global_config_options' => [
                'OPENCAST_TOS',
                'OPENCAST_SHOW_TOS',
                'OPENCAST_ALLOW_ALTERNATE_SCHEDULE' ,
                'OPENCAST_ALLOW_MEDIADOWNLOAD' ,
                'OPENCAST_ALLOW_STUDIO',
                'OPENCAST_ALLOW_SCHEDULER',
                'OPENCAST_HIDE_EPISODES',
                'OPENCAST_TUTOR_EPISODE_PERM',
                'OPENCAST_MEDIA_ROLES',
                'OPENCAST_ALLOW_STUDYGROUP_CONF',
                'OPENCAST_MANAGE_ALL_OC_EVENTS',
                'OPENCAST_RESOURCE_PROPERTY_ID',
                'OPENCAST_SUPPORT_EMAIL',
                'OPENCAST_API_TOKEN',
                'OPENCAST_DEFAULT_SERVER'
            ]
        ];
    }
}
