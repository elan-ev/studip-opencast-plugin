<?php

class CronjobRefreshSeries extends Migration
{
    public function description()
    {
        return 'adds a cronjob for refreshing the episodes of an scheduled series in Stud.IP';
    }
    public function up()
    {
        //add mkdate columns for suitable tables
        DBManager::get()->query("ALTER TABLE `oc_seminar_series` ADD COLUMN `mkdate` INT DEFAULT 0;");
        DBManager::get()->query("ALTER TABLE `oc_seminar_episodes` ADD COLUMN `mkdate` INT DEFAULT 0;");
        DBManager::get()->query("ALTER TABLE `oc_seminar_workflows` ADD COLUMN `mkdate` INT DEFAULT 0;");



        $stmt = DBManager::get()->prepare("UPDATE `oc_seminar_series` SET `mkdate` = ? WHERE 1;");
        $stmt->execute(array(time()));
        $stmt = DBManager::get()->prepare("UPDATE `oc_seminar_episodes` SET `mkdate` = ? WHERE 1;");
        $stmt->execute(array(time()));
        $stmt = DBManager::get()->prepare("UPDATE `oc_seminar_workflows` SET `mkdate` = ? WHERE 1;");
        $stmt->execute(array(time()));

        DBManager::get()->query("CREATE TABLE IF NOT EXISTS `oc_series_cache`  (
              `series_id` varchar(255) NOT NULL,
              `content` longtext NOT NULL,
              `mkdate` INT DEFAULT 0,
              `chdate` INT DEFAULT 0,
              PRIMARY KEY (`series_id`)
              ) ROW_FORMAT=DYNAMIC;");


    }
    function down()
    {

    }
}
