<?php

return [
    "opencast" => [
        "services" => [
            "apievents", // alles admin-node
            "apiseries",
            "apiworkflows",
            "capture-admin",
            "ingest",
            "recordings",
            "search", // ausser hier: engage-node
            "series",
            "services",
            "upload",
            "workflow",
        ],
        "global_config_options" => [
            "OPENCAST_SHOW_TOS",
            "OPENCAST_TOS",
            "OPENCAST_ALLOW_ALTERNATE_SCHEDULE",
            "OPENCAST_MEDIADOWNLOAD",
            "OPENCAST_ALLOW_STUDIO",
            "OPENCAST_ALLOW_SCHEDULER",
            "OPENCAST_HIDE_EPISODES",
            "OPENCAST_TUTOR_EPISODE_PERM",
            "OPENCAST_MEDIA_ROLES",
            "OPENCAST_ALLOW_STUDYGROUP_CONF",
            "OPENCAST_MANAGE_ALL_OC_EVENTS",
            "OPENCAST_CLEAR_RECYCLE_BIN_INTERVAL",
            "OPENCAST_RESOURCE_PROPERTY_ID",
            "OPENCAST_SUPPORT_EMAIL",
            "OPENCAST_API_TOKEN",
            "OPENCAST_DEFAULT_SERVER",
            "OPENCAST_UPLOAD_INFO_TEXT_BODY",
        ],
    ],
];
