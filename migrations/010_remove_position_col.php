<?php

class RemovePositionCol extends Migration
{
    function down()
    {

    }

    function up()
    {
        DBManager::get()->query("ALTER TABLE `oc_seminar_episodes`
            DROP COLUMN `position`;");
    }

}
