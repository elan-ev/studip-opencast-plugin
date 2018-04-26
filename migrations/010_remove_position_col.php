<?php

class RemovePositionCol extends Migration {
    function down()
    {
        DBManager::get()->query("ALTER TABLE `oc_seminar_episodes` ADD COLUMN
              `position` smallint(6) NOT NULL;");
    }

    function up()
    {
        DBManager::get()->query("ALTER TABLE `oc_seminar_episodes`
            DROP COLUMN `position`;");
    }

}
