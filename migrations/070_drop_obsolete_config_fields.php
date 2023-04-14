<?php

class DropObsoleteConfigFields extends Migration
{
    public function description()
    {
        return 'Remove obsolete config fields upload and schedule which reside now in their own table';
    }

    public function up()
    {
        $db = DBManager::get();

        $db->exec("ALTER TABLE `oc_config`
            DROP FOREIGN KEY `oc_config_ibfk_1`,
            DROP FOREIGN KEY `oc_config_ibfk_2`
        ");

        $db->exec("ALTER TABLE `oc_config`
            DROP `upload`,
            DROP `schedule`
        ");

        SimpleOrMap::expireTableScheme();
    }

    public function down()
    {

    }
}