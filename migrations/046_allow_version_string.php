<?php
class AllowVersionString extends Migration
{

    function up()
    {
        $db = DBManager::get();

        $stmt = $db->exec('ALTER TABLE `oc_config`
            CHANGE `service_version` `service_version` varchar(255) NULL
            AFTER `service_password`;
        ');

        SimpleOrMap::expireTableScheme();
    }

    function down()
    {

    }

}
