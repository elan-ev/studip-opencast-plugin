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
        'workflow'
    ];

    static $GLOBAL_CONFIG_ID = -1;
}
