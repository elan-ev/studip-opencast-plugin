<?php
namespace Opencast;

class Constants {
    static $SERVICES = [
        'org.opencastproject.authorization.xacml.manager'  => 'acl-manager',          // alles admin-node
        'org.opencastproject.archive'                      => 'archive',
        'org.opencastproject.external.events'              => 'apievents',
        'org.opencastproject.external'                     => 'apiseries',
        'org.opencastproject.external.workflows.instances' => 'apiworkflows',
        'org.opencastproject.capture.admin'                => 'capture-admin',
        'org.opencastproject.ingest'                       => 'ingest',
        'org.opencastproject.scheduler'                    => 'recordings',
        'org.opencastproject.engage.ui.player.redirect'    => 'play',                 // ausser hier: engage-node
        'org.opencastproject.search'                       => 'search',               // ausser hier: engage-node
        'org.opencastproject.series'                       => 'series',
        'org.opencastproject.serviceregistry'              => 'services',
        'org.opencastproject.workflow'                     => 'workflow',
        'org.opencastproject.adminui.endpoint.event'       => 'admin-ngevent'
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
            'name'        => 'time_buffer_overlap',
            'description' => 'Zeitpuffer (in Sekunden) um Überlappungen zu verhindern',
            'value'       => 60,
            'type'        => 'integer'
        ]
    ];
}
