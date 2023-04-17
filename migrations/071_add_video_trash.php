<?php

class AddVideoTrash extends Migration
{
    const BASE_DIR = 'public/plugins_packages/elan-ev/OpenCast/cronjobs/';

    public function description()
    {
        return 'Add recycle bin functionality for videos';
    }

    public function up()
    {
        $db = DBManager::get();

        $db->exec("ALTER TABLE `oc_video`
            ADD COLUMN `trashed` boolean DEFAULT false,
            ADD COLUMN `trashed_timestamp` timestamp DEFAULT '0000-00-00 00:00:00'");

        // Write video archive to videos table with enabled trash
        $result = $db->query("SELECT * FROM oc_video_archive");
        $stmt = $db->prepare('INSERT IGNORE INTO oc_video (
            token, config_id, episode, title, description, 
            duration, views, preview, publication, visibility,
            created, author, contributors, chdate, mkdate, 
            available, trashed, trashed_timestamp)
          VALUES (
            :token, :config_id, :episode, :title, :description, 
            :duration, :views, :preview, :publication, :visibility, 
            :created, :author, :contributors, :chdate, :mkdate, 
            :available, :trashed, NOW())');
        
        while ($video = $result->fetch(PDO::FETCH_ASSOC)) {
            $stmt->execute([
                'token'          => $video['token'],
                'config_id'      => $video['config_id'],
                'episode'        => $video['episode'],
                'title'          => $video['title'],
                'description'    => $video['description'],
                'duration'       => $video['duration'],
                'views'          => $video['views'],
                'preview'        => $video['preview'],
                'publication'    => $video['publication'],
                'visibility'     => $video['visibility'],
                'created'        => $video['created'],
                'author'         => $video['author'],
                'contributors'   => $video['contributors'],
                'chdate'         => $video['chdate'],
                'mkdate'         => $video['mkdate'],
                'available'      => true,
                'trashed'        => true
            ]);
        }

        $db->exec("DROP TABLE IF EXISTS oc_video_archive");

        SimpleOrMap::expireTableScheme();

        // Add cronjob to remove trashed videos
        $scheduler = CronjobScheduler::getInstance();
        if (!$task_id = CronjobTask::findByFilename(self::BASE_DIR . 'opencast_clear_recycle_bin.php')[0]->task_id) {
            $task_id =  $scheduler->registerTask(self::BASE_DIR . 'opencast_clear_recycle_bin.php', true);
        }

        if ($task_id) {
            $scheduler->cancelByTask($task_id);
            $scheduler->schedulePeriodic($task_id, hour: 22);
            CronjobSchedule::findByTask_id($task_id)[0]->activate();
        }

        // Add global config to edit clear interval of videos in recycle bin
        $stmt = $db->prepare('INSERT IGNORE INTO config (field, value, section, type, `range`, mkdate, chdate, description)
        VALUES (:name, :value, :section, :type, :range, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), :description)');
        $stmt->execute([
            'name'        => 'OPENCAST_CLEAR_RECYCLE_BIN_INTERVAL',
            'section'     => 'opencast',
            'description' => 'Wie viele Tage sollen Videos im Video Archive eines Nutzers erhalten bleiben?',
            'range'       => 'global',
            'type'        => 'integer',
            'value'       => 2
        ]);
    }

    public function down()
    {
        $db = DBManager::get();

        $db->exec("CREATE TABLE IF NOT EXISTS `oc_video_archive` (
            `id` int NOT NULL AUTO_INCREMENT,
            `token` varchar(12),
            `config_id` int,
            `episode` varchar(64) UNIQUE,
            `title` text,
            `description` text,
            `duration` int,
            `views` int,
            `preview` text,
            `publication` text,
            `visibility` enum('internal','free','public') NOT NULL DEFAULT 'internal',
            `created` timestamp,
            `author` varchar(255),
            `contributors` varchar(1000),
            `chdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP(),
            `mkdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP(),
            PRIMARY KEY (`id`),
            FOREIGN KEY (`config_id`) REFERENCES `oc_config`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            KEY `U.1` (`token`),
            KEY `U.2` (`config_id`, `episode`)
        )");

        // Write trashed videos to videos archive table
        $result = $db->query("SELECT * FROM oc_video WHERE trashed=1");
        $stmt = $db->prepare('INSERT IGNORE INTO oc_video_archive (
                token, config_id, episode, title, description, 
                duration, views, preview, publication, visibility,
                created, author, contributors, chdate, mkdate)
            VALUES (
                :token, :config_id, :episode, :title, :description, 
                :duration, :views, :preview, :publication, :visibility, 
                :created, :author, :contributors, :chdate, :mkdate)');
        $rm_stmt = $db->prepare("DELETE FROM oc_video WHERE token = ?");
        
        while ($video = $result->fetch(PDO::FETCH_ASSOC)) {
            $stmt->execute([
                'token'          => $video['token'],
                'config_id'      => $video['config_id'],
                'episode'        => $video['episode'],
                'title'          => $video['title'],
                'description'    => $video['description'],
                'duration'       => $video['duration'],
                'views'          => $video['views'],
                'preview'        => $video['preview'],
                'publication'    => $video['publication'],
                'visibility'     => $video['visibility'],
                'created'        => $video['created'],
                'author'         => $video['author'],
                'contributors'   => $video['contributors'],
                'chdate'         => $video['chdate'],
                'mkdate'         => $video['mkdate'],
            ]);
            $rm_stmt->execute([$video['token']]);
        }

        $db->exec("ALTER TABLE `oc_video` 
            DROP COLUMN `trashed`,
            DROP COLUMN `trashed_timestamp`");

        SimpleOrMap::expireTableScheme();

        // Unregister the cronjob
        $scheduler = CronjobScheduler::getInstance();

        if ($task_id = CronjobTask::findByFilename(self::BASE_DIR . 'opencast_clear_recycle_bin.php')[0]->task_id) {
            $scheduler->unregisterTask($task_id);
        }

        // Remove global config
        $db->exec("DELETE FROM config WHERE field = 'OPENCAST_CLEAR_RECYCLE_BIN_INTERVAL'");
    }
}