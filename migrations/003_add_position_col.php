<?php

class AddPositionCol extends Migration {
    function up() {
        DBManager::get()->query("ALTER TABLE `oc_seminar_episodes` ADD COLUMN
              `position` smallint(6) NOT NULL;");
    }

    function down() {

    }

}