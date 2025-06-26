<?php

class AddOcConfigMaintenanceColumns extends Migration
{
    public function description()
    {
        return 'Add Maintenance mode related columns to oc_config table';
    }

    public function up()
    {
        $db = DBManager::get();

        $db->exec("ALTER TABLE `oc_config`
            ADD COLUMN `maintenance_mode` ENUM('off', 'on', 'read-only') CHARACTER SET latin1 COLLATE latin1_bin NOT NULL DEFAULT 'off',
            ADD COLUMN `maintenance_engage_url_fallback` varchar(255) NOT NULL,
            ADD COLUMN `maintenance_text` TEXT DEFAULT NULL
        ");

        SimpleOrMap::expireTableScheme();
    }

    public function down()
    {
        $db = DBManager::get();

        $db->exec('ALTER TABLE `oc_config`
            DROP COLUMN `maintenance_mode`,
            DROP COLUMN `maintenance_engage_url_fallback`,
            DROP COLUMN `maintenance_text`'
        );

        SimpleOrMap::expireTableScheme();
    }
}
