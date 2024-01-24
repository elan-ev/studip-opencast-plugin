<?php

class AddScheduleFlagsToPlaylistSeminar extends Migration
{
    public function description()
    {
        return 'Add scheduled and livestream recordings columns to playlist seminar table';
    }

    public function up()
    {
        $db = DBManager::get();

        $db->exec('ALTER TABLE `oc_playlist_seminar`
            ADD COLUMN `contains_scheduled` boolean DEFAULT false');

        $db->exec('ALTER TABLE `oc_playlist_seminar`
            ADD COLUMN `contains_livestreams` boolean DEFAULT false');

        // For current default course playlists, we update them here to make sure the default course also contains livestreams and scheduled.
        $db->exec('UPDATE oc_playlist_seminar SET contains_scheduled = 1, contains_livestreams = 1 WHERE is_default = 1');

        SimpleOrMap::expireTableScheme();
    }

    public function down()
    {
        $db = DBManager::get();

        $db->exec('ALTER TABLE `oc_playlist_seminar`
            DROP COLUMN `contains_scheduled`');

        $db->exec('ALTER TABLE `oc_playlist_seminar`
            DROP COLUMN `contains_livestreams`');

        SimpleOrMap::expireTableScheme();
    }
}
