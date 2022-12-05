<?php
class FixConsumersecretName extends Migration
{

    function up()
    {
        $db = DBManager::get();

        DBManager::get()->query("UPDATE `oc_config_precise`
            SET description = 'LTI Consumersecret'
            WHERE name = 'CONSUMERSECRET'");

        SimpleOrMap::expireTableScheme();
    }

    function down()
    {
    }

}
