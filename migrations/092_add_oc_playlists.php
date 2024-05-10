<?php

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__.'/../vendor/autoload.php';

use Opencast\Models\Endpoints;
use Opencast\Models\Playlists;
use Opencast\Models\REST\ApiPlaylistsClient;
use Opencast\Models\Config;

class AddOcPlaylists extends Migration
{
    const CRONJOBS_DIR = 'public/plugins_packages/elan-ev/OpenCast/cronjobs/';

    public function description()
    {
        return 'Add DB columns for Opencast playlists, update Opencast endpoints to find playlists service, migrate existing playlists to Opencast and add playlist synchronize cronjob';
    }

    public function up()
    {
        $db = DBManager::get();

        $db->exec('ALTER TABLE `oc_playlist`
            ADD COLUMN IF NOT EXISTS `config_id` int NULL AFTER `token`,
            ADD COLUMN IF NOT EXISTS `service_playlist_id` varchar(64) UNIQUE NULL AFTER `config_id`,
            ADD COLUMN IF NOT EXISTS `description` text AFTER `title`,
            ADD COLUMN IF NOT EXISTS `creator` varchar(255) AFTER `description`,
            ADD COLUMN IF NOT EXISTS `updated` timestamp AFTER `creator`,
            ADD COLUMN IF NOT EXISTS `available` boolean DEFAULT false,
            ADD FOREIGN KEY IF NOT EXISTS `oc_playlist_ibfk_1` (`config_id`) REFERENCES `oc_config` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
            ADD KEY IF NOT EXISTS `U.2` (`config_id`, `service_playlist_id`)'
        );

        $db->exec('ALTER TABLE `oc_playlist_video`
            ADD COLUMN IF NOT EXISTS `service_entry_id` BIGINT(20) UNSIGNED NULL AFTER `video_id`,
            ADD KEY IF NOT EXISTS `service_entry_id` (`service_entry_id`)'
        );

        SimpleOrMap::expireTableScheme();

        // Update endpoints of all configs to find playlists service
        $configs = Config::findBySQL('1');
        foreach ($configs as $config) {
            $config->updateEndpoints();

            // Check if configured opencast instances support playlists
            if (empty(Endpoints::findOneBySQL("service_type ='apiplaylists' AND config_id = ?", [$config->id]))) {
                throw new Exception("Der Opencast Server ({$config->service_url}) unterstützt keine Playlisten."
                    . " Bitte stellen Sie sicher, dass Sie mindestens die Opencast Version 16 verwenden.");
            }
        }

        // Migrate existing playlists to Opencast
        $playlists = Playlists::findBySQL('service_playlist_id IS NULL');

        // Try three times
        $tries = 0;
        while (!empty($playlists) && $tries < 3) {
            foreach ($playlists as $playlist) {
                $config_id = $playlist->config_id;
                if (empty($config_id)) {
                    // Default opencast server if playlist belongs to no opencast server
                    $config_id = \Config::get()->OPENCAST_DEFAULT_SERVER;
                }

                // Get playlist videos
                $playlist_videos = $this->getPlaylistVideos($playlist);

                $api_playlists_client = ApiPlaylistsClient::getInstance($config_id);
                $oc_playlist = $api_playlists_client->createPlaylist(
                    $this->getOcPlaylistData($playlist, $playlist_videos)
                );

                if ($oc_playlist) {
                    // Set ACLs
                    $oc_playlist = $api_playlists_client->updatePlaylist($oc_playlist->id, [
                        'title' => $oc_playlist->title,
                        'description' => $oc_playlist->description,
                        'creator' => $oc_playlist->creator,
                        'entries' => $oc_playlist->entries,
                        'accessControlEntries' => $this->getDefaultACL($oc_playlist->id)
                    ]);
                }

                if ($oc_playlist) {
                    // Store oc playlist reference in Stud.IP if successfully created
                    $playlist->config_id = $config_id;
                    $playlist->service_playlist_id = $oc_playlist->id;
                    $playlist->store();

                    // Store entry ids
                    for ($i = 0; $i < count($playlist_videos); $i++) {
                        $stmt = $db->prepare("UPDATE oc_playlist_video
                            SET `service_entry_id` = :service_entry_id
                            WHERE playlist_id = :playlist_id AND video_id = :video_id
                        ");
                        $stmt->execute([
                            'service_entry_id' => $oc_playlist->entries[$i]->id,
                            'playlist_id' => $playlist->id,
                            'video_id' => $playlist_videos[$i]['id']
                        ]);
                    }
                }
            }

            $playlists = Playlists::findBySQL('service_playlist_id IS NULL');
            $tries++;
        }

        // Forbid playlist without related oc playlist
        $db->exec('ALTER TABLE `oc_playlist`
            CHANGE COLUMN `config_id` `config_id` int NOT NULL,
            CHANGE COLUMN `service_playlist_id` `service_playlist_id` varchar(64) UNIQUE NOT NULL'
        );

        // Forbid playlist video without related oc playlist entry
        $db->exec('ALTER TABLE `oc_playlist_video`
            ADD COLUMN IF NOT EXISTS `service_entry_id` BIGINT(20) UNSIGNED NOT NULL'
        );

        SimpleOrMap::expireTableScheme();

        // Add playlists sync cronjob
        $scheduler = CronjobScheduler::getInstance();

        if (!$task_id = CronjobTask::findByFilename(self::CRONJOBS_DIR . 'opencast_sync_playlists.php')[0]->task_id) {
            $task_id =  $scheduler->registerTask(self::CRONJOBS_DIR . 'opencast_sync_playlists.php', true);
        }

        // add the new cronjobs
        if ($task_id) {
            $scheduler->cancelByTask($task_id);
            $scheduler->schedulePeriodic($task_id, -10);  // negative value means "every x minutes"
            CronjobSchedule::findByTask_id($task_id)[0]->activate();
        }
    }

    /**
     * Extract oc playlist data from passed playlist
     *
     * @param Playlists $playlist
     * @return array opencast playlist representation
     */
    public function getOcPlaylistData($playlist, $playlist_videos) {
        $owner_names = User::findAndMapBySQL(function ($owner) {
                return $owner->getFullName();
            },
            "INNER JOIN oc_playlist_user_perms as ocpp 
                ON (ocpp.user_id = auth_user_md5.user_id AND ocpp.playlist_id = ?)
                WHERE ocpp.perm = 'owner'",
            [$playlist->id]
        );

        $entries = [];
        foreach ($playlist_videos as $playlist_video) {
            $entries[] = [
                'contentId' => $playlist_video['episode'],
                'type' => 'EVENT'
            ];
        }

        return [
            'title' => $playlist->title,
            'description' => '',
            'creator' => implode(', ', $owner_names),
            'entries' => $entries,
            'accessControlEntries' => [],
        ];
    }

    /**
     * Get event ids of playlist
     *
     * @param Playlists $playlist
     * @return array list of playlist videos
     */
    public function getPlaylistVideos($playlist)
    {
        $sql = "SELECT oc_video.id, oc_video.episode FROM oc_video
            INNER JOIN oc_playlist_video ON (oc_playlist_video.video_id = oc_video.id 
            AND oc_playlist_video.playlist_id = ?)";

        // Get playlist video order
        $allowed_sort_columns = [
            'created', 'title', 'presenters', 'order'
        ];
        list($sort_column, $sort_order)  = explode('_', $playlist->sort_order, 2);
        if (empty($sort_column) || !in_array($sort_column, $allowed_sort_columns, true)) {
            $sort_column = 'created';
            $sort_order = 'DESC';
        }

        if (empty($sort_order) || !in_array($sort_order, ['ASC', 'DESC'], true)) {
            $sort_order = 'ASC';
        }

        if ($sort_column === 'order') {
            $sql .= " ORDER BY oc_playlist_video.order $sort_order";
        } else {
            $sql .= " ORDER BY oc_video.$sort_column $sort_order";
        }

        $stmt = \DBManager::get()->prepare($sql);
        $stmt->execute([$playlist->id]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }


    /**
     * Get default ACL for playlists
     *
     * @param string $playlist_id playlist id
     * @return array[] ACLs list
     */
    public function getDefaultACL($playlist_id)
    {
        return [
            [
                'allow' => true,
                'role' => "STUDIP_PLAYLIST_{$playlist_id}_read",
                'action' => 'read'
            ],
            [
                'allow' => true,
                'role' => "STUDIP_PLAYLIST_{$playlist_id}_write",
                'action' => 'read'
            ],
            [
                'allow' => true,
                'role' => "STUDIP_PLAYLIST_{$playlist_id}_write",
                'action' => 'write'
            ]
        ];
    }

    public function down()
    {
        $db = DBManager::get();

        $db->exec('ALTER TABLE `oc_playlist`
            DROP COLUMN IF EXISTS `config_id`,
            DROP COLUMN IF EXISTS `service_playlist_id`,
            DROP COLUMN IF EXISTS `description`,
            DROP COLUMN IF EXISTS `creator`,
            DROP COLUMN IF EXISTS `updated`,
            DROP FOREIGN KEY IF EXISTS `oc_playlist_ibfk_1`,
            DROP KEY IF EXISTS `U.2`'
        );

        $db->exec('ALTER TABLE `oc_playlist_video`
            DROP COLUMN IF EXISTS `service_entry_id`,
            DROP KEY IF EXISTS `service_entry_id`'
        );

        SimpleOrMap::expireTableScheme();

        // Remove playlists sync cronjob
        if ($task_id = CronjobTask::findByFilename(self::CRONJOBS_DIR . 'opencast_sync_playlists.php')[0]->task_id) {
            $task_id = CronjobScheduler::getInstance()->unregisterTask($task_id);
        }
    }
}
