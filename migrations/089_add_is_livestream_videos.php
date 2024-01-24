<?php

class AddIsLivestreamVideos extends Migration
{
    public function description()
    {
        return 'Add is_livestream columns to videos';
    }

    public function up()
    {
        $db = DBManager::get();

        $db->exec('ALTER TABLE `oc_video` ADD COLUMN IF NOT EXISTS
            `is_livestream` boolean DEFAULT false');

        SimpleOrMap::expireTableScheme();
    }

    public function down()
    {
        $db = DBManager::get();

        $db->exec('ALTER TABLE `oc_video`
            DROP COLUMN IF EXISTS `is_livestream`');

        SimpleOrMap::expireTableScheme();
    }
}
