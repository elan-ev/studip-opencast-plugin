<?php

require_once __DIR__ . '/../bootstrap_migrations.php';
require_once __DIR__.'/../vendor/autoload.php';

use Opencast\Models\Endpoints;
use Opencast\Models\Playlists;
use Opencast\Models\Config;
use Opencast\Helpers\PlaylistMigration;

class AddOcPlaylists extends Migration
{
    const CRONJOBS_DIR = 'public/plugins_packages/elan-ev/OpencastV3/cronjobs/';

    public function description()
    {
        return 'Add DB columns for Opencast playlists, update Opencast endpoints to find playlists service, migrate existing playlists to Opencast and add playlist synchronize cronjob';
    }

    public function up()
    {
        $db = DBManager::get();

        $db->exec('ALTER TABLE `oc_playlist`
            ADD COLUMN `config_id` int NULL AFTER `token`,
            ADD COLUMN `service_playlist_id` varchar(64) UNIQUE NULL AFTER `config_id`,
            ADD COLUMN `description` text AFTER `title`,
            ADD COLUMN `creator` varchar(255) AFTER `description`,
            ADD COLUMN `updated` timestamp AFTER `creator`,
            ADD COLUMN `available` boolean DEFAULT false,
            ADD FOREIGN KEY `oc_playlist_ibfk_1` (`config_id`) REFERENCES `oc_config` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
            ADD KEY `U.2` (`config_id`, `service_playlist_id`)'
        );

        $db->exec('ALTER TABLE `oc_playlist_video`
            ADD COLUMN `service_entry_id` BIGINT(20) UNSIGNED NULL AFTER `video_id`,
            ADD KEY `service_entry_id` (`service_entry_id`)'
        );

        SimpleOrMap::expireTableScheme();

        // Update endpoints of all configs to find playlists service
        $configs = Config::findBySQL('1');
        $migrate = !empty($configs);

        foreach ($configs as $config) {
            $config->updateEndpoints();

            // Check if configured opencast instances support playlists
            if (empty(Endpoints::findOneBySQL("service_type ='apiplaylists' AND config_id = ?", [$config->id]))) {
                // throw new Exception("Der Opencast Server ({$config->service_url}) unterstützt keine Playlisten."
                //     . " Bitte stellen Sie sicher, dass Sie mindestens die Opencast Version 16 verwenden.");
                $migrate = false;
            }
        }

        if ($migrate) {
            // all opencast system seem to support playlist, do the migration right away
            PlaylistMigration::convert();
        }
    }

    public function down()
    {
        $db = DBManager::get();

        $db->exec('ALTER TABLE `oc_playlist`
            DROP COLUMN `config_id`,
            DROP COLUMN `service_playlist_id`,
            DROP COLUMN `description`,
            DROP COLUMN `creator`,
            DROP COLUMN `updated`,
            DROP FOREIGN KEY `oc_playlist_ibfk_1`,
            DROP KEY `U.2`'
        );

        $db->exec('ALTER TABLE `oc_playlist_video`
            DROP COLUMN `service_entry_id`,
            DROP KEY `service_entry_id`'
        );

        SimpleOrMap::expireTableScheme();

        // Remove playlists sync cronjob
        if ($task_id = CronjobTask::findByFilename(self::CRONJOBS_DIR . 'opencast_sync_playlists.php')[0]->task_id) {
            $task_id = CronjobScheduler::getInstance()->unregisterTask($task_id);
        }
    }
}
