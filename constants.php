<?php
namespace Opencast;

class Constants {
    static $SERVICES = [
        'acl-manager',          // alles admin-node
        'archive',
        'apievents',
        'apiseries',
        'apiworkflows',
        'capture-admin',
        'ingest',
        'recordings',
        'search',               // ausser hier: engage-node
        'series',
        'services',
        'workflow',
        'admin-ngevent'
    ];

    static $GLOBAL_CONFIG_OPTIONS = [
        'OPENCAST_SHOW_TOS',
        'OPENCAST_ALLOW_ALTERNATE_SCHEDULE' ,
        'OPENCAST_ALLOW_MEDIADOWNLOAD' ,
        'OPENCAST_ALLOW_STUDIO',
        'OPENCAST_HIDE_EPISODES',
        'OPENCAST_TUTOR_EPISODE_PERM',
        'OPENCAST_ALLOW_STUDYGROUP_CONF',
        'OPENCAST_MANAGE_ALL_OC_EVENTS',
        'OPENCAST_RESOURCE_PROPERTY_ID',
        'OPENCAST_SUPPORT_EMAIL'
    ];

    static $DEFAULT_CONFIG = [
        [
            'name'        => 'service_url',
            'description' => 'Basis URL zur Opencast Installation',
            'value'       => '',
            'type'        => 'string',
            'required'    => true
        ],
        [
            'name'        => 'service_user',
            'description' => 'Nutzerkennung',
            'value'       => '',
            'type'        => 'string',
            'required'    => true
        ],
        [
            'name'        => 'service_password',
            'description' => 'Passwort',
            'value'       => '',
            'type'        => 'password',
            'required'    => true
        ],
        [
            'name'        => 'lti_consumerkey',
            'description' => 'LTI Consumerkey',
            'value'       => 'CONSUMERKEY',
            'type'        => 'string',
            'required'    => true
        ],
        [
            'name'        => 'lti_consumersecret',
            'description' => 'LTI Consumersecret',
            'value'       => 'CONSUMERSECRET',
            'type'        => 'password',
            'required'    => true
        ],
        [
            'name'        => 'paella',
            'description' => 'Soll der Paella Player verwendet werden statt Theodul?',
            'value'       => 1,
            'type'        => 'boolean'
        ],
        [
            'name'        => 'time_buffer_overlap',
            'description' => 'Zeitpuffer (in Sekunden) um Überlappungen zu verhindern',
            'value'       => 60,
            'type'        => 'integer'
        ]
    ];
}
