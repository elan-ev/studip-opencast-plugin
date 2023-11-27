<?php

class AddAllowDownloadPlaylistSeminar extends Migration
{
    public function description()
    {
        return 'Add allow download column to playlist seminar table';
    }

    public function up()
    {
        $db = DBManager::get();

        $db->exec('ALTER TABLE `oc_playlist_seminar`
            ADD COLUMN `allow_download` boolean NULL');

        SimpleOrMap::expireTableScheme();
    }

    public function down()
    {
        $db = DBManager::get();

        $db->exec('ALTER TABLE `oc_playlist_seminar`
            DROP COLUMN `allow_download`');

        SimpleOrMap::expireTableScheme();
    }
}