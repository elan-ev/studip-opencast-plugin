<?php
class CreatePlaylistVideosAuditLogTable extends Migration
{
    const FILENAME = 'public/plugins_packages/elan-ev/OpencastV3/cronjobs/opencast_playlist_videos_audit_cleanup.php';

    public function description()
    {
        return 'Create a new table to log changes to playlist videos, in order to keep track of deletions and modifications. Additionally adds a cronjob to remove old entries.';
    }

    public function up()
    {
        $db = DBManager::get();

        $db->exec("CREATE TABLE IF NOT EXISTS `oc_playlist_videos_audit_log` (
            `id` int NOT NULL AUTO_INCREMENT,
            `playlist_id` int,
            `video_id` int,
            `action` enum('add','delete','restore') NOT NULL DEFAULT 'add',
            `mkdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP(),
            `chdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP(),
            PRIMARY KEY (`id`),
            FOREIGN KEY (`playlist_id`) REFERENCES `oc_playlist`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            FOREIGN KEY (`video_id`) REFERENCES `oc_video`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            INDEX `idx_playlist_video_mkdate` (`playlist_id`, `video_id`, `mkdate` DESC)
        )");

        SimpleOrMap::expireTableScheme();

        // Add cronjob to clean up old entries
        $scheduler = CronjobScheduler::getInstance();
        $task_id = $scheduler->registerTask(self::FILENAME, true);
        if ($task_id) {
            $scheduler->schedulePeriodic($task_id, null, null, -28); // negative value means "every 28 days" => every 4 weeks!
        }
    }

    public function down()
    {
        $db = DBManager::get();
        $db->exec("DROP TABLE IF EXISTS oc_playlist_videos_audit_log");

        SimpleOrMap::expireTableScheme();

        // Unregister cronjob!
        $scheduler = CronjobScheduler::getInstance();

        if ($task_id = CronjobTask::findByFilename(self::FILENAME)[0]->task_id) {
            $scheduler->unregisterTask($task_id);
        }
    }
}
