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

        $db->exec('ALTER TABLE oc_playlist_video
            ADD COLUMN `available` INT NOT NULL DEFAULT 1 AFTER video_id');
    }

    public function down()
    {

    }
}