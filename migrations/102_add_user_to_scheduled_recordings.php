<?php

class AddUserToScheduledRecordings extends Migration
{
    public function description()
    {
        return 'Add user id to oc_scheduled_recordings to identify the user who scheduled the recording';
    }

    public function up()
    {
        $db = DBManager::get();

        $db->exec('ALTER TABLE `oc_scheduled_recordings`
            ADD COLUMN `user_id` varchar(32) CHARACTER SET latin1 COLLATE latin1_bin NULL AFTER `series_id`');
    }

    public function down()
    {
        $db = DBManager::get();

        $db->exec('ALTER TABLE `oc_scheduled_recordings` DROP COLUMN `user_id`');
    }
}