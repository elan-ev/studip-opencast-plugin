<?php

class AddPlaylistAvailability extends Migration
{
    public function description()
    {
        return 'Add availability status to videos added to course playlists and allow for different types of sync jobs';
    }

    public function up()
    {
        $db = DBManager::get();

        $stmt = $db->exec('ALTER TABLE oc_playlist_video
            ADD COLUMN `available` INT NOT NULL DEFAULT 1 AFTER video_id');

        $stmt = $db->exec("ALTER TABLE oc_video_sync
            ADD COLUMN `type` ENUM('video', 'playlistvideo') NOT NULL DEFAULT 'video' AFTER `state`,
            ADD COLUMN `data` TEXT NULL AFTER `type`
        ");
    }

    public function down()
    {

    }
}