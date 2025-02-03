<?php

class WorkflowConfiguration extends Migration {
    function up() {

        DBManager::get()->query("CREATE TABLE IF NOT EXISTS `oc_seminar_workflow_configuration` (
              `seminar_id` varchar(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
              `workflow_id` varchar(255) NOT NULL,
              `target` ENUM('schedule', 'upload') CHARACTER SET latin1 COLLATE latin1_bin NOT NULL DEFAULT 'schedule',
              `mkdate` INT DEFAULT 0,
              `chdate` INT DEFAULT 0,
              PRIMARY KEY (`seminar_id`, `target`)
              );");

        DBManager::get()->query("ALTER TABLE `oc_scheduled_recordings`
                                    ADD COLUMN `workflow_id` VARCHAR(255) DEFAULT 'full',
                                    ADD COLUMN mktime INT DEFAULT 0,
                                    ADD COLUMN chdate INT DEFAULT 0,
                                    CHANGE status status ENUM('scheduled','recorded','uploaded','processed') CHARACTER SET latin1 COLLATE latin1_bin NOT NULL DEFAULT 'scheduled'
                                    ;");
    }




    function down() {

    }

}