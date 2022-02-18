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
            'type'        => 'string',
            'required'    => true
        ],
        [
            'name'        => 'upload_chunk_size',
            'description' => 'Größe der Chunks für das Hochladen in Byte',
            'value'       => 5000000,
            'type'        => 'integer'
        ],
        [
            'name'        => 'time_buffer_overlap',
            'description' => 'Zeitpuffer (in Sekunden) um Überlappungen zu verhindern',
            'value'       => 60,
            'type'        => 'integer'
        ],
        [
            'name'        => 'debug',
            'description' => 'Debugmodus einschalten?',
            'value'       =>  0,
            'type'        => 'boolen'
        ]
    ];
}
