<?php

class AddVideoAvailability extends Migration
{
    public function description()
    {
        return 'Add timestamp to make video visible for course';
    }

    public function up()
    {
        $db = DBManager::get();

        $db->exec("ALTER TABLE `oc_video`
            ADD COLUMN `available` boolean DEFAULT false");

        SimpleOrMap::expireTableScheme();
    }

    public function down()
    {
        $db = DBManager::get();

        $db->exec("ALTER TABLE `oc_video` DROP COLUMN `available`");

        SimpleOrMap::expireTableScheme();
    }
}