<?php
class ServiceVersionString extends Migration
{
    public function description()
    {
        return 'Add config to show or hide the scheduler functionality';
    }

    public function up()
    {
        $db = DBManager::get();

        $db->exec("
            ALTER TABLE oc_config
            CHANGE `service_version` `service_version` varchar(255) NULL;
        ");


        SimpleOrMap::expireTableScheme();
    }

    public function down()
    {
        SimpleOrMap::expireTableScheme();
    }
}