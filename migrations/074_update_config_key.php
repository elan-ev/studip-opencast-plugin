<?php

class UpdateConfigKey extends Migration
{
    public function description()
    {
        return 'Update oc_config key to only use the service_url';
    }

    public function up()
    {
        $db = DBManager::get();

        $db->exec("ALTER TABLE `oc_config`
            DROP INDEX service_url,
            ADD UNIQUE KEY(service_url)
        ");

        SimpleOrMap::expireTableScheme();
    }

    public function down()
    {
        $db = DBManager::get();

        $db->exec("ALTER TABLE `oc_config`
            DROP INDEX service_url,
            ADD UNIQUE KEY(service_url,service_user,service_password)
        ");

        SimpleOrMap::expireTableScheme();
    }
}