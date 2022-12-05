<?php
class DropOcAccessControl extends Migration
{

    function up()
    {
        DBManager::get()->query("DROP TABLE IF EXISTS `oc_access_control`");

        SimpleOrMap::expireTableScheme();
    }

    function down()
    {
    }

}
