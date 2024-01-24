<?php

class AddIsLivestreamScheduledRecordings extends Migration
{
    public function description()
    {
        return 'Add is_livestream columns to scheduled recordings';
    }

    public function up()
    {
        $db = DBManager::get();

        $db->exec('ALTER TABLE `oc_scheduled_recordings` ADD COLUMN IF NOT EXISTS
            `is_livestream` boolean DEFAULT false');

        SimpleOrMap::expireTableScheme();
    }

    public function down()
    {
        $db = DBManager::get();

        $db->exec('ALTER TABLE `oc_scheduled_recordings`
            DROP COLUMN IF EXISTS `is_livestream`');

        SimpleOrMap::expireTableScheme();
    }
}
