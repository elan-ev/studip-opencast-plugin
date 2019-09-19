<?php
namespace Opencast;

class Constants {
    static $UPLOAD_CHUNK_SIZE = '10000000';

    static $SERVICES = [
        'acl-manager',          // alles admin-node
        'archive',
        'apiworkflows',
        'capture-admin',
        'ingest',
        'recordings',
        'search',               // ausser hier: engage-node
        'series',
        'services',
        'upload',
        'workflow'
    ];

    static $GLOBAL_CONFIG_ID = -1;
}
