<?php
class AddAclQueue extends Migration
{
    public function up()
    {
        $db = DBManager::get();

        $db->exec("CREATE TABLE IF NOT EXISTS `oc_acl_queue` (
            `episode_id` varchar(64) NOT NULL,
            `trys` INT NOT NULL DEFAULT 0,
            `mkdate` INT NOT NULL DEFAULT 0,
            `chdate` INT NOT NULL DEFAULT 0,
            PRIMARY KEY (`episode_id`)
        )");

        SimpleOrMap::expireTableScheme();
    }

    public function down()
    {
        $db = DBManager::get();
        $db->exec('DROP TABLE IF EXISTS `oc_acl_queue`');
    }
}
