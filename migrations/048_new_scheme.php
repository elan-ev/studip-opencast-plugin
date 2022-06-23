<?php
class NewScheme extends Migration
{

    function up()
    {
        $sql = [];

        $sql[] = 'SET foreign_key_checks = 0';

        $sql[] = "CREATE TABLE `oc_playlist` (
            `id` int NOT NULL AUTO_INCREMENT,
            `token` varchar(1),
            `title` text,
            `description` text,
            `visibility` enum('internal','free','public'),
            `chdate` timestamp,
            `mkdate` timestamp,
            PRIMARY KEY (`id`),
            KEY `U.1` (`token`)
          );
        ";

        $sql[] = "CREATE TABLE `oc_workflow_config` (
            `id` int NOT NULL AUTO_INCREMENT,
            `config_id` int,
            `workflow` varchar(255),
            `displayname` varchar(255),
            PRIMARY KEY (`id`),
            FOREIGN KEY (`config_id`) REFERENCES `oc_config`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            KEY `U.1` (`config_id`, `workflow`)
          );
        ";

        $sql[] = "ALTER TABLE `oc_config`
            ADD `upload` int NULL,
            ADD `schedule` int NULL,
            ADD FOREIGN KEY (`upload`) REFERENCES `oc_workflow_config` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
            ADD FOREIGN KEY (`schedule`) REFERENCES `oc_workflow_config` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
        ";

        $sql[] = "ALTER TABLE oc_endpoints
          DROP service_host,
          ADD FOREIGN KEY (`config_id`) REFERENCES `oc_config`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;
        ";

        $sql[] = "CREATE TABLE `oc_video_sync` (
            `id` int NOT NULL AUTO_INCREMENT,
            `video_id` int,
            `state` enum('running','scheduled','failed'),
            `scheduled` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE CURRENT_TIMESTAMP,
            `trys` int,
            `chdate` timestamp,
            `mkdate` timestamp,
            PRIMARY KEY (`id`),
            KEY `U.1` (`video_id`)
          );
        ";

        $sql[] = "CREATE TABLE `oc_video` (
            `id` int NOT NULL AUTO_INCREMENT,
            `token` varchar(12),
            `config_id` int,
            `episode` varchar(64),
            `title` text,
            `description` text,
            `duration` int,
            `views` int,
            `preview` text,
            `publication` text,
            `visibility` enum('internal','free','public'),
            `chdate` timestamp,
            `mkdate` timestamp,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`config_id`) REFERENCES `oc_config`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,   # disallow deletion of config if videos are assigned to that config
            KEY `U.1` (`token`),
            KEY `U.2` (`config_id`, `episode`)
          );
        ";

        $sql[] = "CREATE TABLE `oc_playlist_video` (
            `playlist_id` int,
            `video_id` int,
            `order` int,
            PRIMARY KEY (`playlist_id`, `video_id`),
            FOREIGN KEY (`playlist_id`) REFERENCES `oc_playlist`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            FOREIGN KEY (`video_id`) REFERENCES `oc_video`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            KEY `U.1` (`playlist_id`, `order`)
          );
        ";

        $sql[] = "CREATE TABLE `oc_tags` (
            `id` int NOT NULL AUTO_INCREMENT,
            `tag` varchar(255),
            PRIMARY KEY (`id`),
            KEY `U.1` (`tag`)
          );
        ";

        $sql[] = "CREATE TABLE `oc_playlist_tags` (
            `playlist_id` int,
            `tag_id` int,
            PRIMARY KEY (`playlist_id`, `tag_id`),
            FOREIGN KEY (`tag_id`) REFERENCES `oc_tags`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            FOREIGN KEY (`playlist_id`) REFERENCES `oc_playlist`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
          );
        ";

        $sql[] ="ALTER TABLE `oc_seminar_series`
            DROP schedule,
            ADD `chdate` timestamp,
            CHANGE `mkdate` `mkdate` timestamp,
            ADD FOREIGN KEY (`config_id`) REFERENCES `oc_config`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
            ADD KEY `U.1` (`series_id`, `config_id`);
        ";


        $sql[] = "CREATE TABLE `oc_capture_agent` (
            `id` int NOT NULL AUTO_INCREMENT,
            `config_id` int,
            `capture_agent` varchar(64),
            PRIMARY KEY (`id`),
            FOREIGN KEY (`config_id`) REFERENCES `oc_config`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            KEY `U.1` (`config_id`, `capture_agent`)
          );
        ";

        $sql[] = "CREATE TABLE `oc_capture_agent_resource` (
            `resource_id` varchar(32),
            `capture_agent_id` int,
            `workflow_config_id` int,
            PRIMARY KEY (`resource_id`, `capture_agent_id`),
            FOREIGN KEY (`workflow_config_id`) REFERENCES `oc_workflow_config`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            FOREIGN KEY (`capture_agent_id`) REFERENCES `oc_capture_agent`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
          );
        ";

        $sql[] = "CREATE TABLE `oc_playlist_seminar` (
            `id` int NOT NULL AUTO_INCREMENT,
            `playlist_id` int,
            `seminar_id` varchar(32),
            `visibility` enum('hidden','visible'),
            PRIMARY KEY (`id`),
            FOREIGN KEY (`playlist_id`) REFERENCES `oc_playlist`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            KEY `U.1` (`playlist_id`, `seminar_id`)
          );
        ";

        // Allows setting the visibility for videos in playlists in seminars
        $sql[] = "CREATE TABLE `oc_playlist_seminar_video` (
            `playlist_seminar_id` int,
            `video_id` int,
            `visibility` enum('hidden','visible'),
            PRIMARY KEY (`playlist_seminar_id`, `video_id`),
            FOREIGN KEY (`video_id`) REFERENCES `oc_video`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            FOREIGN KEY (`playlist_seminar_id`) REFERENCES `oc_playlist_seminar`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
          );
        ";

        // video directlu associated to a seminar - without playlist
        $sql[] = "CREATE TABLE `oc_video_seminar` (
            `video_id` int,
            `seminar_id` varchar(32),
            `visibility` enum('hidden','visible'),
            PRIMARY KEY (`video_id`, `seminar_id`),
            FOREIGN KEY (`video_id`) REFERENCES `oc_video`(`id`)  ON DELETE CASCADE ON UPDATE CASCADE
          );
        ";

        $sql[] = "ALTER TABLE `oc_tos`
            DROP `seminar_id`,
            ADD  `mkdate` timestamp,
            DROP PRIMARY KEY,
            ADD PRIMARY KEY (`user_id`);
        ";

        $sql[] = "CREATE TABLE `oc_video_user_perms` (
            `video_id` int,
            `user_id` varchar(32),
            `perm` enum('owner','write','read','share'),
            PRIMARY KEY (`video_id`, `user_id`, `perm`),
            FOREIGN KEY (`video_id`) REFERENCES `oc_video`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
          );
        ";

        $sql[] = "CREATE TABLE `oc_video_tags` (
            `video_id` int,
            `tag_id` int,
            PRIMARY KEY (`video_id`, `tag_id`),
            FOREIGN KEY (`tag_id`) REFERENCES `oc_tags`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            FOREIGN KEY (`video_id`) REFERENCES `oc_video`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
          );
        ";

        $sql[] = "CREATE TABLE `oc_playlist_user_perms` (
            `playlist_id` int,
            `user_id` varchar(32),
            `perm` enum('owner','write','read','share'),
            PRIMARY KEY (`playlist_id`, `user_id`, `perm`),
            FOREIGN KEY (`playlist_id`) REFERENCES `oc_playlist`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
          );
        ";

        $sql[] = "CREATE TABLE `oc_workflow_config_scope` (
            `workflow_config_id` int,
            `scope` enum('schedule','upload'),
            PRIMARY KEY (`workflow_config_id`, `scope`),
            FOREIGN KEY (`workflow_config_id`) REFERENCES `oc_workflow_config`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
          );
        ";

        // TODO: what to make of the upload studygroups? At the UOS these are so few, we can safely ignore them
        $sql[] = "DROP TABLE oc_upload_studygroup";

        $sql[] = 'SET foreign_key_checks = 1';

        $db = DBManager::get();

        foreach ($sql as $query) {
            $db->exec($query);
        }

        SimpleOrMap::expireTableScheme();
    }

    function down()
    {
        // There is no going back from this migration!!!
    }

}

