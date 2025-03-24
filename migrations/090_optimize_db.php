<?php

class OptimizeDB extends Migration
{
    public function description()
    {
        return 'Optimize db tables regarding collation and indexes';
    }

    public function up()
    {
        $db = DBManager::get();

        // Remove orphan rows of deleted courses
        $db->exec("DELETE oc FROM `oc_cw_block_copy_mapping` AS oc
            LEFT JOIN seminare ON seminare.Seminar_id = oc.new_seminar_id WHERE seminare.Seminar_id IS NULL
       ");

        $db->exec("DELETE oc FROM `oc_playlist_seminar` AS oc
            LEFT JOIN seminare ON seminare.Seminar_id = oc.seminar_id WHERE seminare.Seminar_id IS NULL
       ");

        $db->exec("DELETE oc FROM `oc_scheduled_recordings` AS oc
            LEFT JOIN seminare ON seminare.Seminar_id = oc.seminar_id WHERE seminare.Seminar_id IS NULL
       ");

        $db->exec("DELETE oc FROM `oc_seminar_series` AS oc
            LEFT JOIN seminare ON seminare.Seminar_id = oc.seminar_id WHERE seminare.Seminar_id IS NULL
       ");

        $db->exec("DELETE oc FROM `oc_seminar_workflow_configuration` AS oc
            LEFT JOIN seminare ON seminare.Seminar_id = oc.seminar_id WHERE seminare.Seminar_id IS NULL
       ");


        // use latin1 for ids, enums and sort order, and improve indexes
        $db->exec("ALTER TABLE `oc_playlist`
            MODIFY `token` varchar(8) CHARACTER SET latin1 COLLATE latin1_bin UNIQUE,
            MODIFY `visibility` enum ('internal', 'free', 'public') CHARACTER SET latin1 COLLATE latin1_bin,
            MODIFY `sort_order` varchar(30) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL DEFAULT 'created_desc'
        ");

        $db->exec("ALTER TABLE `oc_cw_block_copy_mapping`
            MODIFY `new_seminar_id` varchar(32) CHARACTER SET latin1 COLLATE latin1_bin,
            ADD FOREIGN KEY (`new_seminar_id`) REFERENCES `seminare` (`Seminar_id`) ON DELETE CASCADE
        ");

        $db->exec("ALTER TABLE `oc_playlist_seminar`
            MODIFY `seminar_id` varchar(32) CHARACTER SET latin1 COLLATE latin1_bin,
            MODIFY `visibility` enum ('hidden', 'visible') CHARACTER SET latin1 COLLATE latin1_bin,
            DROP INDEX `U.1`,
            ADD FOREIGN KEY (`seminar_id`) REFERENCES `seminare` (`Seminar_id`) ON DELETE CASCADE,
            ADD KEY `U.1` (`playlist_id`)
        ");

        $db->exec("ALTER TABLE `oc_playlist_seminar_video`
            MODIFY `visibility` enum ('hidden', 'visible') CHARACTER SET latin1 COLLATE latin1_bin
        ");

        $db->exec("ALTER TABLE `oc_playlist_user_perms`
            MODIFY `user_id` varchar(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
            MODIFY `perm` enum('owner','write','read','share') CHARACTER SET latin1 COLLATE latin1_bin NOT NULL
        ");

        $db->exec("ALTER TABLE `oc_resources`
            MODIFY `resource_id` varchar(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL
        ");

        $db->exec("ALTER TABLE `oc_scheduled_recordings`
            MODIFY `seminar_id` varchar(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
            MODIFY `resource_id` varchar(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
            MODIFY `status` enum ('scheduled', 'recorded', 'uploaded', 'processed') CHARACTER SET latin1 COLLATE latin1_bin NOT NULL DEFAULT 'scheduled',
            ADD FOREIGN KEY (`seminar_id`) REFERENCES `seminare` (`Seminar_id`) ON DELETE CASCADE
        ");

        $db->exec("ALTER TABLE `oc_seminar_series`
            MODIFY `seminar_id` varchar(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
            ADD FOREIGN KEY (`seminar_id`) REFERENCES `seminare` (`Seminar_id`) ON DELETE CASCADE
        ");

        $db->exec("ALTER TABLE `oc_seminar_workflow_configuration`
            MODIFY `seminar_id` varchar(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
            MODIFY `target` enum ('schedule', 'upload') CHARACTER SET latin1 COLLATE latin1_bin NOT NULL DEFAULT 'schedule',
            ADD FOREIGN KEY (`seminar_id`) REFERENCES `seminare` (`Seminar_id`) ON DELETE CASCADE
        ");

        $db->exec("ALTER TABLE `oc_tags`
            DROP KEY `U.1`,
            ADD UNIQUE KEY `U.1` (`user_id`, `tag`)
        ");

        $db->exec("ALTER TABLE `oc_user_series`
            MODIFY `user_id` varchar(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
            MODIFY `visibility` enum ('visible', 'invisible') CHARACTER SET latin1 COLLATE latin1_bin NOT NULL
        ");

        // Drop token to allow changes
        $db->exec("ALTER TABLE `oc_video_cw_blocks`
            DROP FOREIGN KEY `token`,
            MODIFY `token` varchar(12) CHARACTER SET latin1 COLLATE latin1_bin
        ");

        $db->exec("ALTER TABLE `oc_video`
            MODIFY `token` varchar(12) CHARACTER SET latin1 COLLATE latin1_bin UNIQUE,
            MODIFY `visibility` enum ('internal', 'free', 'public') CHARACTER SET latin1 COLLATE latin1_bin NOT NULL DEFAULT 'internal'
        ");

        // Re-add token
        $db->exec("ALTER TABLE `oc_video_cw_blocks`
            ADD FOREIGN KEY `token` (`token`) REFERENCES `oc_video` (`token`) ON DELETE CASCADE ON UPDATE RESTRICT
        ");

        $db->exec("ALTER TABLE `oc_video_shares`
            MODIFY `token` varchar(16) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
            MODIFY `uuid` varchar(32) CHARACTER SET latin1 COLLATE latin1_bin
        ");

        $db->exec("ALTER TABLE `oc_video_user_perms`
            MODIFY `user_id` varchar(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
            MODIFY `perm` enum ('owner', 'write', 'read', 'share') CHARACTER SET latin1 COLLATE latin1_bin
        ");

        $db->exec("ALTER TABLE `oc_workflow_config`
            MODIFY `used_for` enum ('schedule', 'upload', 'studio', 'delete') CHARACTER SET latin1 COLLATE latin1_bin
        ");

        SimpleOrMap::expireTableScheme();
    }

    public function down() {}
}