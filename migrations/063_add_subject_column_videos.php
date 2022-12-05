<?php

class AddSubjectColumnVideos extends Migration
{
    public function description()
    {
        return 'Add subject column to table videos';
    }

    public function up()
    {
        $db = DBManager::get();

        $db->exec("ALTER TABLE `oc_video`
            ADD COLUMN `subject` text DEFAULT NULL AFTER title");

        SimpleOrMap::expireTableScheme();
    }

    public function down()
    {
        $db = DBManager::get();

        $db->exec("ALTER TABLE `oc_video` DROP COLUMN `subject`");

        SimpleOrMap::expireTableScheme();
    }
}
