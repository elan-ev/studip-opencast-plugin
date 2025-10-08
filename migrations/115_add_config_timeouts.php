<?php

class AddConfigTimeouts extends Migration
{
    public function description()
    {
        return 'Add server timeout and connection timeout config options';
    }

    public function up()
    {
        $db = DBManager::get();

        $db->exec("ALTER TABLE `oc_config`
            ADD COLUMN `timeout_ms` INT DEFAULT 0 AFTER `service_version`,
            ADD COLUMN `connect_timeout_ms` INT DEFAULT 2000 AFTER `timeout_ms`
        ");

        SimpleOrMap::expireTableScheme();
    }

    public function down()
    {
        $db = DBManager::get();

        $db->exec('ALTER TABLE `oc_config`
            DROP COLUMN `timeout_ms`,
            DROP COLUMN `connect_timeout_ms`
        ');
    }
}
