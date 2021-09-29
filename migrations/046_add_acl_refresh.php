<?php
class AddACLRefresh extends Migration
{
    const FILENAME = 'public/plugins_packages/elan-ev/OpenCast/cronjobs/refresh_acls.php';

    public function up()
    {
        $db = DBManager::get();

        $db->exec("CREATE TABLE IF NOT EXISTS `oc_seminar_acl_refresh` (
            `seminar_id` varchar(32) NOT NULL,
            `running` BOOLEAN NOT NULL DEFAULT FALSE,
            `mkdate` INT NOT NULL DEFAULT 0,
            `chdate` INT NOT NULL DEFAULT 0,
            PRIMARY KEY (`seminar_id`)
        )");

        SimpleOrMap::expireTableScheme();

        $task_id = CronjobScheduler::registerTask(self::FILENAME, true);

        if ($task_id) {
            CronjobScheduler::schedulePeriodic($task_id);
        }
    }

    public function down()
    {
        $db = DBManager::get();
        $db->exec('DROP TABLE IF EXISTS `oc_seminar_acl_refresh`');

        if ($task_id = CronjobTask::findByFilename(self::FILENAME)->task_id) {
            CronjobScheduler::unregisterTask($task_id);
        }
    }
}
