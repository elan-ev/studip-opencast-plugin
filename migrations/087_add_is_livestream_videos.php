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

        $db->exec('ALTER TABLE `oc_video` ADD COLUMN
            `is_livestream` boolean DEFAULT false');

        SimpleOrMap::expireTableScheme();
    }

    public function down()
    {
        $db = DBManager::get();

        $db->exec('ALTER TABLE `oc_video`
            DROP COLUMN `is_livestream`');

        SimpleOrMap::expireTableScheme();
    }
}
