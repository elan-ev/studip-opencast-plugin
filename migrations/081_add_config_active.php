<?php

class AddConfigActive extends Migration
{
    public function description()
    {
        return 'Add "active" property to config, so a server config can be disabled';
    }

    public function up()
    {
        $db = DBManager::get();

        $db->exec("ALTER TABLE `oc_config`
            ADD `active` Boolean DEFAULT TRUE
        ");

        SimpleOrMap::expireTableScheme();
    }

    public function down()
    {
        $db = DBManager::get();

        $db->exec('ALTER TABLE `oc_config` DROP `active` ');

        SimpleOrMap::expireTableScheme();
    }
}