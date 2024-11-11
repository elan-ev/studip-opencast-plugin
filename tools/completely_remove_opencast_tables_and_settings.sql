SET foreign_key_checks = 0;

DROP TABLE IF EXISTS `oc_config`,
    `oc_cw_block_copy_mapping`,
    `oc_endpoints`,
    `oc_playlist`,
    `oc_playlist_seminar`,
    `oc_playlist_seminar_video`,
    `oc_playlist_tags`,
    `oc_playlist_user_perms`,
    `oc_playlist_video`,
    `oc_resources`,
    `oc_scheduled_recordings`,
    `oc_seminar_series`,
    `oc_seminar_episodes`,
    `oc_seminar_workflows`,
    `oc_seminar_workflow_configuration`,
    `oc_series_cache`,
    `oc_tags`,
    `oc_tos`,
    `oc_user_series`,
    `oc_video`,
    `oc_video_archive`,
    `oc_video_cw_blocks`,
    `oc_video_seminar`,
    `oc_video_shares`,
    `oc_video_sync`,
    `oc_video_tags`,
    `oc_video_user_perms`,
    `oc_workflow`,
    `oc_workflow_config`;

DELETE FROM schema_version WHERE domain = 'OpenCast';
DELETE FROM schema_version WHERE domain = 'OpencastV3';
DELETE FROM config WHERE section = 'opencast';
DELETE FROM config_values WHERE field LIKE 'OPENCAST_%';
DELETE FROM plugins WHERE pluginclassname = 'OpenCast';
DELETE FROM plugins WHERE pluginclassname = 'OpencastV3';
DELETE FROM cronjobs_tasks WHERE class LIKE 'Opencast%' OR class = 'RefreshScheduledEvents' OR class = 'RefreshSeries';
DELETE FROM cronjobs_schedules WHERE task_id IN (SELECT task_id FROM cronjobs_schedules LEFT JOIN cronjobs_tasks USING (task_id) WHERE cronjobs_tasks.task_id IS NULL);

DELETE FROM log_events WHERE action_id IN (
    SELECT action_id FROM log_actions WHERE name LIKE 'OC_%'
);
DELETE FROM log_actions WHERE name LIKE 'OC_%';

SET foreign_key_checks = 1;