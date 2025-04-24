SET FOREIGN_KEY_CHECKS=0;

REPLACE INTO `oc_config`
    (`id`, `service_url`, `service_user`, `service_password`, `service_version`, `settings`) VALUES
    (1,	'http://127.0.0.1:8081', 'admin', 'opencast', '16.6', '{\"lti_consumerkey\":\"CONSUMERKEY\",\"lti_consumersecret\":\"CONSUMERSECRET\"}');


REPLACE INTO `oc_endpoints` (`config_id`, `service_url`, `service_type`) VALUES
(1,	'http://127.0.0.1:8081/api/events',	'apievents'),
(1,    'http://127.0.0.1:8081/api/playlists', 'apiplaylists'),
(1,	'http://127.0.0.1:8081/api/series',	'apiseries'),
(1,	'http://127.0.0.1:8081/api/workflows',	'apiworkflows'),
(1,	'http://127.0.0.1:8081/capture-admin',	'capture-admin'),
(1,	'http://127.0.0.1:8081/ingest',	'ingest'),
(1,    'http://127.0.0.1:8081/play', 'play'),
(1,	'http://127.0.0.1:8081/recordings',	'recordings'),
(1,	'http://127.0.0.1:8081/search',	'search'),
(1,	'http://127.0.0.1:8081/series',	'series'),
(1,	'http://127.0.0.1:8081/services',	'services'),
(1,	'http://127.0.0.1:8081/upload',	'upload'),
(1,	'http://127.0.0.1:8081/workflow',	'workflow');


REPLACE INTO `config_values` (`field`, `range_id`, `value`, `mkdate`, `chdate`, `comment`) VALUES
('OPENCAST_API_TOKEN',	'studip',	'mytoken1234abcdef',	1693295334,	1693295334,	'');


REPLACE INTO `config_values` (`field`, `range_id`, `value`, `mkdate`, `chdate`, `comment`) VALUES
('OPENCAST_DEFAULT_SERVER ',	'studip',	'1',	1693295334,	1693295334,	'');

REPLACE INTO `roles_plugins` (`roleid`, `pluginid`) VALUES
(7,	29);


REPLACE INTO `auth_user_md5` (`user_id`, `username`, `password`, `perms`, `Vorname`, `Nachname`, `Email`, `validation_key`, `auth_plugin`, `locked`, `lock_comment`, `locked_by`, `visible`) VALUES
('fad0229f8b0573cda5fbdf5fcfa89362', 'simple_autor', 0x24326124303824756C6A4D587969786C71376939634D6539775841364F526C612E466C6E46743370762F45754E5150356C58516A775070634442462E, 'autor', 'Simple', 'Autor', 'test@studip.de', '', 'standard', 0, NULL, NULL, 'unknown');

UPDATE auth_user_md5 SET visible = 'always' WHERE 1;

REPLACE INTO config_values (field, range_id, value) VALUES ('TERMS_ACCEPTED', '205f3efb7997a0fc9755da2b535038da', 1);
REPLACE INTO config_values (field, range_id, value) VALUES ('TERMS_ACCEPTED', '6235c46eb9e962866ebdceece739ace5', 1);
REPLACE INTO config_values (field, range_id, value) VALUES ('TERMS_ACCEPTED', '76ed43ef286fb55cf9e41beadb484a9f', 1);
REPLACE INTO config_values (field, range_id, value) VALUES ('TERMS_ACCEPTED', '7e81ec247c151c02ffd479511e24cc03', 1);
REPLACE INTO config_values (field, range_id, value) VALUES ('TERMS_ACCEPTED', 'e7a0a84b161f3e8c09b4a0a2e8a58147', 1);
REPLACE INTO config_values (field, range_id, value) VALUES ('TERMS_ACCEPTED', 'fad0229f8b0573cda5fbdf5fcfa89362', 1);

-- activate plugin in course
REPLACE INTO tools_activated
    (range_id, range_type, plugin_id, position, metadata, mkdate, chdate) VALUES
('a07535cf2f8a72df33c12ddfa4b53dde', 'course', 29, 11, '[]', 1699267230, 1699267230);


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

REPLACE INTO `auth_user_md5` (`user_id`, `username`, `password`, `perms`, `Vorname`, `Nachname`, `Email`, `validation_key`, `auth_plugin`, `locked`, `lock_comment`, `locked_by`, `visible`) VALUES
('e7a0a84b161f3e8c09b4a0a2e8a58147', 'simple_autor', UNHEX('2432612430382437614E434250676A4D535039426F666B684B4D4252754A32564F5164324D6E616E726E57782F4469627A5547462F416D3248654436'), 'autor', 'Testaccount',	'Simple Autor', 'autor@studip.de', '', 'standard', 0, NULL, NULL, 'yes');

SET FOREIGN_KEY_CHECKS=1;
