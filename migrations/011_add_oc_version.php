<?php

class AddOcVersion extends Migration
{

    function up()
    {
        DBManager::get()->query("ALTER TABLE `oc_config` ADD COLUMN
              `service_version` int(11) NULL;");
    }

    function down()
    {
        DBManager::get()->query("ALTER TABLE `oc_config`
            DROP COLUMN `service_version`;");
    }

}
