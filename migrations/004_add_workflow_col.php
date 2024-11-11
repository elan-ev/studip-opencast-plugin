<?php

class AddWorkflowCol extends Migration {
    function up() {
        DBManager::get()->query("ALTER TABLE `oc_resources` ADD COLUMN
              `workflow_id` varchar(64) NOT NULL;");
    }

    function down() {

    }

}