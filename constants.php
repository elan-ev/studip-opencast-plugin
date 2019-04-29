<?php
namespace Opencast;

class Constants {
    static $UPLOAD_CHUNK_SIZE = '10000000';

    static $SERVICES = [
        'acl-manager',
        'archive',
        'capture-admin',
        'ingest',
        'recordings',
        'search',
        'series',
        'services',
        'upload',
        'workflow'
    ];

    static $GLOBAL_CONFIG_ID = -1;
}
