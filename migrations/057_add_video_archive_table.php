<?php
class AddVideoArchiveTable extends Migration
{
    public function description()
    {
        return 'Add config to show or hide the scheduler functionality';
    }

    public function up()
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


        SimpleOrMap::expireTableScheme();
    }

    public function down()
    {
        $db = DBManager::get();
        $db->exec("DROP TABLE IF EXISTS oc_video_archive");

        SimpleOrMap::expireTableScheme();
    }
}