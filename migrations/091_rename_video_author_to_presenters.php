<?php

class RenameVideoAuthorToPresenters extends Migration
{
    public function description()
    {
        return 'Rename author to presenters in video table';
    }

    public function up()
    {
        $db = DBManager::get();

        $db->exec("ALTER TABLE `oc_video`
            CHANGE COLUMN `author` `presenters` varchar(255)
        ");

        SimpleOrMap::expireTableScheme();
    }

    public function down()
    {
        $db = DBManager::get();

        $db->exec("ALTER TABLE `oc_video`
            CHANGE COLUMN `presenters` `author` varchar(255)
        ");

        SimpleOrMap::expireTableScheme();
    }
}
