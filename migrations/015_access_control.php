<?php
/**
 * @author          Jan-Frederik Leissner <jleissner@uos.de>
 * @copyright   (c) Authors
 * @version         1.0 (14:00)
 */

class AccessControl extends Migration
{

    function up()
    {
        $stmt = DBManager::get()->query("CREATE TABLE IF NOT EXISTS `oc_access_control` (
            `id` TEXT NOT NULL ,
            `type` ENUM('episode','series') NOT NULL ,
            `course_id` TEXT NOT NULL ,
            `acl_id` INT NOT NULL
        )");

        DBManager::get()->query("ALTER TABLE `oc_seminar_episodes`
            ADD `permission` ENUM('allowed', 'forbidden') NOT NULL DEFAULT 'forbidden'");

        // Expire orm cache, so the change can take effect
        SimpleORMap::expireTableScheme();
    }

    function down()
    {
        $stmt = DBManager::get()->query("DROP TABLE `oc_access_control`");

        SimpleORMap::expireTableScheme();
    }

}
