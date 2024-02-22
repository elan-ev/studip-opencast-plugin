<?php

class AddEventState extends Migration
{
    public function description()
    {
        return 'Add event state to videos, for enabling link to cutting tool and more';
    }

    public function up()
    {
        $db = DBManager::get();

        $db->exec("ALTER TABLE `oc_video`
            ADD `state` VARCHAR(64) DEFAULT NULL AFTER publication
        ");

        SimpleOrMap::expireTableScheme();
    }

    public function down()
    {
        $db = DBManager::get();

        $db->exec('ALTER TABLE `oc_video` DROP `state` ');

        SimpleOrMap::expireTableScheme();
    }
}