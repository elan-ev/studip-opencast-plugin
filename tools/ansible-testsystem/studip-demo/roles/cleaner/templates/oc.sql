SET FOREIGN_KEY_CHECKS=0;

REPLACE INTO `oc_config`
    (`id`, `service_url`, `service_user`, `service_password`, `service_version`, `settings`) VALUES
    (1,	'{{ opencast_uri }}', 'admin', 'opencast', '15.0', '{\"lti_consumerkey\":\"CONSUMERKEY\",\"lti_consumersecret\":\"CONSUMERSECRET\"}');


REPLACE INTO `oc_endpoints` (`config_id`, `service_url`, `service_type`) VALUES
(1,	'{{ opencast_uri }}/api/events',	'apievents'),
(1,	'{{ opencast_uri }}/api/series',	'apiseries'),
(1,	'{{ opencast_uri }}/api/workflows',	'apiworkflows'),
(1,	'{{ opencast_uri }}/capture-admin',	'capture-admin'),
(1,	'{{ opencast_uri }}/ingest',	'ingest'),
(1,	'{{ opencast_uri }}/recordings',	'recordings'),
(1,	'{{ opencast_uri }}/search',	'search'),
(1,	'{{ opencast_uri }}/series',	'series'),
(1,	'{{ opencast_uri }}/services',	'services'),
(1,	'{{ opencast_uri }}/upload',	'upload'),
(1,	'{{ opencast_uri }}/workflow',	'workflow');


REPLACE INTO `config_values` (`field`, `range_id`, `value`, `mkdate`, `chdate`, `comment`) VALUES
('OPENCAST_API_TOKEN',	'studip',	'mytoken1234abcdef',	1693295334,	1693295334,	'');


REPLACE INTO `config_values` (`field`, `range_id`, `value`, `mkdate`, `chdate`, `comment`) VALUES
('OPENCAST_DEFAULT_SERVER ',	'studip',	'1',	1693295334,	1693295334,	'');

REPLACE INTO `roles_plugins` (`roleid`, `pluginid`) VALUES
(7,	29);

UPDATE auth_user_md5 SET visible = 'always' WHERE 1;

REPLACE INTO config_values (field, range_id, value) VALUES ('TERMS_ACCEPTED', '205f3efb7997a0fc9755da2b535038da', 1);
REPLACE INTO config_values (field, range_id, value) VALUES ('TERMS_ACCEPTED', '6235c46eb9e962866ebdceece739ace5', 1);
REPLACE INTO config_values (field, range_id, value) VALUES ('TERMS_ACCEPTED', '76ed43ef286fb55cf9e41beadb484a9f', 1);
REPLACE INTO config_values (field, range_id, value) VALUES ('TERMS_ACCEPTED', '7e81ec247c151c02ffd479511e24cc03', 1);
REPLACE INTO config_values (field, range_id, value) VALUES ('TERMS_ACCEPTED', 'e7a0a84b161f3e8c09b4a0a2e8a58147', 1);

-- add videos so foreign keys are working
REPLACE INTO `oc_video` (`id`, `config_id`, `episode`, `available`, `duration`) VALUES
(1,	1,	'ID-goat',	1,	NULL),
(2,	1,	'ID-weitsprung',	1,	NULL),
(3,	1,	'ID-nasa-earth-4k',	1,	NULL),
(4,	1,	'ID-strong-river-flowing-down-the-green-forest',	1,	NULL),
(5,	1,	'ID-marguerite',	1,	NULL),
(6,	1,	'ID-espresso-video',	1,	NULL),
(7,	1,	'ID-westerberg',	1,	NULL),
(8,	1,	'ID-cats',	1,	NULL),
(9,	1,	'ID-spring',	1,	NULL),
(10,	1,	'ID-dog-rose',	1,	NULL),
(11,	1,	'ID-nasa-rocket-booster',	1,	NULL),
(12,	1,	'ID-was-ist-chaos',	1,	NULL),
(13,	1,	'ID-3d-print',	1,	NULL),
(14,	1,	'ID-perseverance-arrives-at-mars',	1,	NULL),
(15,	1,	'ID-pendulum-with-spring-damper',	1,	NULL),
(16,	1,	'ID-coffee-run',	1,	NULL),
(17,	1,	'ID-lavender',	1,	NULL),
(18,	1,	'ID-subtitle-demo',	1,	NULL),
(19,	1,	'ID-about-opencast',	1,	NULL),
(20,	1,	'ID-dual-stream-demo',	1,	NULL);


REPLACE INTO `oc_video_sync`
    VALUES (1,1,'scheduled','2023-11-10 11:06:02',NULL,'0000-00-00 00:00:00','0000-00-00 00:00:00'),
    (2,2,'scheduled','2023-11-10 11:06:02',NULL,'0000-00-00 00:00:00','0000-00-00 00:00:00'),
    (3,3,'scheduled','2023-11-10 11:06:02',NULL,'0000-00-00 00:00:00','0000-00-00 00:00:00'),
    (4,4,'scheduled','2023-11-10 11:06:03',NULL,'0000-00-00 00:00:00','0000-00-00 00:00:00'),
    (5,5,'scheduled','2023-11-10 11:06:03',NULL,'0000-00-00 00:00:00','0000-00-00 00:00:00'),
    (6,6,'scheduled','2023-11-10 11:06:03',NULL,'0000-00-00 00:00:00','0000-00-00 00:00:00'),
    (7,7,'scheduled','2023-11-10 11:06:03',NULL,'0000-00-00 00:00:00','0000-00-00 00:00:00'),
    (8,8,'scheduled','2023-11-10 11:06:03',NULL,'0000-00-00 00:00:00','0000-00-00 00:00:00'),
    (9,9,'scheduled','2023-11-10 11:06:03',NULL,'0000-00-00 00:00:00','0000-00-00 00:00:00'),
    (10,10,'scheduled','2023-11-10 11:06:03',NULL,'0000-00-00 00:00:00','0000-00-00 00:00:00'),
    (11,11,'scheduled','2023-11-10 11:06:03',NULL,'0000-00-00 00:00:00','0000-00-00 00:00:00'),
    (12,12,'scheduled','2023-11-10 11:06:03',NULL,'0000-00-00 00:00:00','0000-00-00 00:00:00'),
    (13,13,'scheduled','2023-11-10 11:06:03',NULL,'0000-00-00 00:00:00','0000-00-00 00:00:00'),
    (14,14,'scheduled','2023-11-10 11:06:04',NULL,'0000-00-00 00:00:00','0000-00-00 00:00:00'),
    (15,15,'scheduled','2023-11-10 11:06:04',NULL,'0000-00-00 00:00:00','0000-00-00 00:00:00'),
    (16,16,'scheduled','2023-11-10 11:06:04',NULL,'0000-00-00 00:00:00','0000-00-00 00:00:00'),
    (17,17,'scheduled','2023-11-10 11:06:04',NULL,'0000-00-00 00:00:00','0000-00-00 00:00:00'),
    (18,18,'scheduled','2023-11-10 11:06:04',NULL,'0000-00-00 00:00:00','0000-00-00 00:00:00'),
    (19,19,'scheduled','2023-11-10 11:06:04',NULL,'0000-00-00 00:00:00','0000-00-00 00:00:00'),
    (20,20,'scheduled','2023-11-10 11:06:04',NULL,'0000-00-00 00:00:00','0000-00-00 00:00:00');

-- allow test_dozent access to videos
REPLACE INTO oc_video_user_perms
    (video_id, user_id, perm) VALUES
(1, '205f3efb7997a0fc9755da2b535038da', 'owner'),
(2, '205f3efb7997a0fc9755da2b535038da', 'owner'),
(3, '205f3efb7997a0fc9755da2b535038da', 'owner'),
(4, '205f3efb7997a0fc9755da2b535038da', 'owner'),
(5, '205f3efb7997a0fc9755da2b535038da', 'owner'),
(6, '205f3efb7997a0fc9755da2b535038da', 'owner'),
(7, '205f3efb7997a0fc9755da2b535038da', 'owner'),
(8, '205f3efb7997a0fc9755da2b535038da', 'owner'),
(9, '205f3efb7997a0fc9755da2b535038da', 'owner'),
(10, '205f3efb7997a0fc9755da2b535038da', 'owner'),
(11, '205f3efb7997a0fc9755da2b535038da', 'owner'),
(12, '205f3efb7997a0fc9755da2b535038da', 'owner'),
(13, '205f3efb7997a0fc9755da2b535038da', 'owner'),
(14, '205f3efb7997a0fc9755da2b535038da', 'owner'),
(15, '205f3efb7997a0fc9755da2b535038da', 'owner'),
(16, '205f3efb7997a0fc9755da2b535038da', 'owner'),
(17, '205f3efb7997a0fc9755da2b535038da', 'owner'),
(18, '205f3efb7997a0fc9755da2b535038da', 'owner'),
(19, '205f3efb7997a0fc9755da2b535038da', 'owner'),
(20, '205f3efb7997a0fc9755da2b535038da', 'owner');

-- activate plugin in course
REPLACE INTO tools_activated
    (range_id, range_type, plugin_id, position, metadata, mkdate, chdate) VALUES
('a07535cf2f8a72df33c12ddfa4b53dde', 'course', 29, 11, '[]', 1699267230, 1699267230);

-- add videos to course playlist
REPLACE INTO oc_playlist
    (id, token, config_id, service_playlist_id, title, visibility, chdate, mkdate, sort_order, allow_download) VALUES
    (1, 'fce2a63c', 1, 'studip-playlist', '12345 Test Lehrveranstaltung (WS 2023/2024)', NULL, '2023-11-10 12:50:57', '2023-11-10 12:50:57', 'created_desc', NULL);

REPLACE INTO `oc_playlist_seminar` (`id`, `playlist_id`, `seminar_id`, `is_default`, `visibility`) VALUES
    (1,	1,	'a07535cf2f8a72df33c12ddfa4b53dde',	1,	'visible');

REPLACE INTO oc_playlist_video
    (playlist_id, video_id, `order`) VALUES
(1, 1, 0),
(1, 2, 0),
(1, 3, 0),
(1, 4, 0),
(1, 5, 0),
(1, 6, 0),
(1, 7, 0),
(1, 8, 0),
(1, 9, 0),
(1, 10, 0),
(1, 11, 0),
(1, 12, 0),
(1, 13, 0),
(1, 14, 0),
(1, 15, 0),
(1, 16, 0),
(1, 17, 0),
(1, 18, 0),
(1, 19, 0),
(1, 20, 0);


REPLACE INTO `oc_workflow` (`id`, `config_id`, `name`, `tag`, `displayname`) VALUES
(1,	1,	'delete',	'delete',	'Delete'),
(2,	1,	'duplicate-event',	'archive',	'Duplicate Event'),
(3,	1,	'fast',	'schedule',	'Fast Testing Workflow'),
(4,	1,	'fast',	'upload',	'Fast Testing Workflow'),
(5,	1,	'schedule-and-upload',	'schedule',	'Process upon upload and schedule'),
(6,	1,	'schedule-and-upload',	'upload',	'Process upon upload and schedule'),
(7,	1,	'publish',	'archive',	'Publish'),
(8,	1,	'publish',	'editor',	'Publish'),
(9,	1,	'republish-metadata',	'archive',	'Republish metadata'),
(10,	1,	'retract',	'archive',	'Retract');


REPLACE INTO `oc_workflow_config` (`id`, `config_id`, `used_for`, `workflow_id`) VALUES
(1,	1,	'schedule',	5),
(2,	1,	'upload',	6),
(3,	1,	'studio',	6),
(4,	1,	'delete',	1),
(5,	1,	'subtitles',	9);

SET FOREIGN_KEY_CHECKS=1;
