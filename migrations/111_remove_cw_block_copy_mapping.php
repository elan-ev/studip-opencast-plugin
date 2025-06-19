<?php

class RemoveCwBlockCopyMapping extends Migration
{
    const CRONJOB_FILE = 'public/plugins_packages/elan-ev/OpencastV3/cronjobs/opencast_courseware_block_copy_mapping.php';

    public function description()
    {
        return 'Remove courseware block copy mapping tables and cronjob.';
    }

    public function up()
    {
        $scheduler = CronjobScheduler::getInstance();

        // remove worker cronjob
        if ($task_id = CronjobTask::findByFilename(self::CRONJOB_FILE)[0]->task_id) {
            $scheduler->unregisterTask($task_id);
        }

        $db = DBManager::get();

        $db->exec('DROP TABLE IF EXISTS `oc_cw_block_copy_mapping`');
        $db->exec('DROP TABLE IF EXISTS `oc_video_cw_blocks`');
    }

    public function down()
    {
        $db = DBManager::get();

        $db->exec("CREATE TABLE IF NOT EXISTS `oc_cw_block_copy_mapping` (
            `id` int NOT NULL AUTO_INCREMENT,
            `token` varchar(32),
            `video_id` int,
            `new_seminar_id` varchar(32) CHARACTER SET latin1 COLLATE latin1_bin,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`new_seminar_id`) REFERENCES `seminare` (`Seminar_id`) ON DELETE CASCADE
        );");

        $db->exec("CREATE TABLE IF NOT EXISTS `oc_video_cw_blocks` (
            `token` varchar(12) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
            `block_id` int NOT NULL,
            `seminar_id` char(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
            PRIMARY KEY `token_block_id` (`token`, `block_id`),
            FOREIGN KEY `token` (`token`) REFERENCES `oc_video` (`token`) ON DELETE CASCADE ON UPDATE RESTRICT,
            FOREIGN KEY (`seminar_id`) REFERENCES `seminare` (`Seminar_id`) ON DELETE CASCADE
        );");


        // Fill table oc_video_cw_blocks

        // Database statements
        $insert_block_stmt = $db->prepare('INSERT IGNORE INTO `oc_video_cw_blocks` (token, block_id, seminar_id)
                VALUES (:token, :block_id, :seminar_id)');

        $blocks = $db->query("SELECT cw_blocks.id, cw_blocks.payload, range_id FROM cw_blocks
            INNER JOIN cw_containers ON (cw_blocks.container_id = cw_containers.id)
            INNER JOIN cw_structural_elements ON (cw_containers.structural_element_id = cw_structural_elements.id)
            WHERE block_type = 'plugin-opencast-video' AND range_type = 'course'");

        // Iterate over all opencast blocks
        while ($block = $blocks->fetch(PDO::FETCH_ASSOC)) {
            $payload = json_decode($block['payload'], true);

            if (isset($payload['token'])) {
                // Insert row to oc_video_cw_blocks and ignore if exists
                $insert_block_stmt->execute([
                    ':token'      => $payload['token'],
                    ':block_id'   => $block['id'],
                    ':seminar_id' => $block['range_id'],
                ]);
            }
        }

        // Re-add cronjob
        if (file_exists($GLOBALS['STUDIP_BASE_PATH'] . '/' . self::CRONJOB_FILE)) {
            $scheduler = CronjobScheduler::getInstance();
            $task_id = $scheduler->registerTask(self::CRONJOB_FILE, true);


            if ($task_id) {
                $scheduler->schedulePeriodic($task_id, -1);
            }
        }
    }
}
