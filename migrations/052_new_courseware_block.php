<?php
class NewCoursewareBlock extends Migration
{
    const FILENAME = 'public/plugins_packages/elan-ev/OpenCast/cronjobs/opencast_courseware_block_copy_mapping.php';

    public function description()
    {
        return 'Creates the oc_video_cw_blocks and oc_cw_block_copy tables and cronjob for mapping';
    }

    public function up()
    {
        DBManager::get()->exec("CREATE TABLE IF NOT EXISTS `oc_video_cw_blocks` (
            `video_id` int,
            `block_id` int,
            `seminar_id` char(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
            PRIMARY KEY (`video_id`, `block_id`),
            FOREIGN KEY (`video_id`) REFERENCES `oc_video` (`id`) ON DELETE CASCADE,
            FOREIGN KEY (`block_id`) REFERENCES `cw_blocks` (`id`) ON DELETE CASCADE,
            FOREIGN KEY (`seminar_id`) REFERENCES `seminare` (`Seminar_id`) ON DELETE CASCADE
        );");

        DBManager::get()->exec("CREATE TABLE IF NOT EXISTS `oc_cw_block_copy_mapping` (
            `id` int NOT NULL AUTO_INCREMENT,
            `token` varchar(32),
            `video_id` int,
            `new_seminar_id` varchar(32),
            PRIMARY KEY (`id`)
        );");

        // Cronjob
        if (file_exists($GLOBALS['STUDIP_BASE_PATH'] . '/' . self::FILENAME)) {
            $task_id = CronjobScheduler::registerTask(self::FILENAME, true);

            if ($task_id) {
                CronjobScheduler::schedulePeriodic($task_id, -1);
            }
        }

        SimpleOrMap::expireTableScheme();
    }

    public function down()
    {
        DBManager::get()->query("DROP TABLE IF EXISTS `oc_video_cw_blocks`;");
        DBManager::get()->query("DROP TABLE IF EXISTS `oc_cw_block_copy_mapping`;");

        if (file_exists($GLOBALS['STUDIP_BASE_PATH'] . '/' . self::FILENAME)) {
            if ($task_id = CronjobTask::findByFilename(self::FILENAME)->task_id) {
                CronjobScheduler::unregisterTask($task_id);
            }
        }

        SimpleOrMap::expireTableScheme();
    }
}