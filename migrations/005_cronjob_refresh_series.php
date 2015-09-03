<?php

class CronjobRefreshSeries extends Migration
{
    const FILENAME = 'public/plugins_packages/elan-ev/OpenCast/cronjobs/refresh_series.php';
    public function description()
    {
        return 'adds a cronjob for refreshing the episodes of an scheduled series in Stud.IP';
    }
    public function up()
    {
        $task_id = CronjobScheduler::registerTask(self::FILENAME, true);

        // Schedule job to run every 30 minutes
        if ($task_id) {
            CronjobScheduler::schedulePeriodic($task_id, -30);  // negative value means "every x minutes"
        }

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
              );");


    }
    function down()
    {
        if ($task_id = CronjobTask::findByFilename(self::FILENAME)->task_id) {
            CronjobScheduler::unregisterTask($task_id);
        }


        DBManager::get()->query("ALTER TABLE `oc_seminar_episodes` DROP COLUMN `mkdate`;");
        DBManager::get()->query("ALTER TABLE `oc_seminar_series` DROP COLUMN `mkdate`;");
        DBManager::get()->query("ALTER TABLE `oc_seminar_workflows` DROP COLUMN `mkdate`;");

        DBManager::get()->query("DROP TABLE IF EXISTS `oc_series_cache`;");
    }
}