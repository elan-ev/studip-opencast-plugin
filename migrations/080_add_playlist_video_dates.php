<?php

class AddPlaylistVideoDates extends Migration
{
    public function description()
    {
        return 'Add mkdate and chdate columns to table playlist video';
    }

    public function up()
    {
        $db = DBManager::get();

        $db->exec("ALTER TABLE `oc_playlist_video`
            ADD COLUMN IF NOT EXISTS `chdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP(),
            ADD COLUMN IF NOT EXISTS `mkdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP()
        ");

        SimpleOrMap::expireTableScheme();
    }

    public function down()
    {
        $db = DBManager::get();

        $db->exec("ALTER TABLE `oc_playlist_video` DROP COLUMN `chdate`, DROP COLUMN `mkdate`");

        SimpleOrMap::expireTableScheme();
    }
}
