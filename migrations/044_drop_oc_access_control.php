<?php
class DropOcAccessControl extends Migration
{

    function up()
    {
        DBManager::get()->query("DROP TABLE `oc_access_control`");

        SimpleOrMap::expireTableScheme();
    }

    function down()
    {
    }

}
