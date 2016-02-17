<?php

class WorkflowConfiguration extends Migration {
    function up() {

        DBManager::get()->query("CREATE TABLE IF NOT EXISTS `oc_seminar_workflow_configuration` (
              `seminar_id` varchar(32) NOT NULL,
              `workflow_id` varchar(255) NOT NULL,
              `target` ENUM('schedule', 'upload') NOT NULL DEFAULT 'schedule',
              `mkdate` INT DEFAULT 0,
              `chdate` INT DEFAULT 0,
              PRIMARY KEY (`seminar_id`, `target`)
              );");

        DBManager::get()->query("ALTER TABLE `oc_scheduled_recordings`
                                    ADD COLUMN `workflow_id` VARCHAR(255) DEFAULT 'full',
                                    ADD COLUMN mktime INT DEFAULT 0,
                                    ADD COLUMN chdate INT DEFAULT 0,
                                    CHANGE status status ENUM('scheduled','recorded','uploaded','processed') NOT NULL DEFAULT 'scheduled'
                                    ;");
    }




    function down() {
        DBManager::get()->query("DROP TABLE IF EXISTS `oc_seminar_workflow_configuration`;");
        DBManager::get()->query("ALTER TABLE `oc_scheduled_recordings`
                                  DROP COLUMN `workflow_id`,
                                  DROP COLUMN `mktime`,
                                  DROP COLUMN `chdate`,
                                  CHANGE status status ENUM('scheduled','recorded') NOT NULL DEFAULT 'scheduled';");
    }

}