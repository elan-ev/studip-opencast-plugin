<?php

class AddVisibleTimestamp extends Migration
{
    public function description()
    {
        return 'Add timestamp to make video visible for course';
    }

    public function up()
    {
        $db = DBManager::get();

        $db->exec("ALTER TABLE `oc_playlist_seminar_video`
            ADD COLUMN `visible_timestamp` timestamp DEFAULT '0000-00-00 00:00:00'");

        SimpleOrMap::expireTableScheme();
    }

    public function down()
    {
        $db = DBManager::get();

        $db->exec("ALTER TABLE `oc_playlist_seminar_video` DROP COLUMN `visible_timestamp`");

        SimpleOrMap::expireTableScheme();
    }
}