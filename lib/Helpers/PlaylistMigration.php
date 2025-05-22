<?php

namespace Opencast\Helpers;

use Opencast\Models\Playlists;
use Opencast\Models\REST\ApiPlaylistsClient;
use DBManager;

class PlaylistMigration
{
    const CRONJOBS_DIR = 'public/plugins_packages/elan-ev/OpencastV3/cronjobs/';

    /**
     * Convert the Stud.IP playlists to OC playlists
     *
     * @return void
     */
    public static function convert()
    {
        $db = DBManager::get();

        // Default opencast server if playlist belongs to no opencast server
        $default_config_id = \Config::get()->OPENCAST_DEFAULT_SERVER;

        try {
            // Migrate existing playlists to Opencast
            $playlists = Playlists::findBySQL('service_playlist_id IS NULL');

            // Try three times
            $tries = 0;
            while (!empty($playlists) && $tries < 10) {
                foreach ($playlists as $playlist) {
                    $config_id = $playlist->config_id ?? null;
                    if (empty($config_id)) {
                        // Default opencast server if playlist belongs to no opencast server
                        $config_id = $default_config_id;
                    }

                    // Get playlist videos
                    $playlist_videos = self::getPlaylistVideos($playlist);

                    $api_playlists_client = ApiPlaylistsClient::getInstance($config_id);
                    $oc_playlist = $api_playlists_client->createPlaylist(
                        self::getOcPlaylistData($playlist, $playlist_videos)
                    );

                    if ($oc_playlist) {
                        // Store oc playlist reference in Stud.IP if successfully created
                        $playlist->config_id = $config_id;
                        $playlist->service_playlist_id = $oc_playlist->id;
                        $playlist->store();

                        Playlists::checkPlaylistACL($oc_playlist, $playlist);

                        // Store entry ids
                        for ($i = 0; $i < count($playlist_videos); $i++) {
                            $stmt = $db->prepare("UPDATE oc_playlist_video
                                SET `service_entry_id` = :service_entry_id
                                WHERE playlist_id = :playlist_id AND video_id = :video_id
                            ");
                            $stmt->execute($data = [
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

            // What is the point of letting the process continue if there are still playlists with null service_playlist_id of duplicated service_playlist_ids?
            $duplicate_service_playlist_ids = $db->query(
                "SELECT service_playlist_id, COUNT(*) as count
                FROM oc_playlist
                WHERE service_playlist_id IS NOT NULL
                GROUP BY service_playlist_id
                HAVING count > 1"
            )->fetchAll(\PDO::FETCH_ASSOC);

            if (!empty($playlists) || !empty($duplicate_service_playlist_ids)) {
                $message = "Migration failed due to invalid data records:\n";
                if (!empty($playlists)) {
                    $message .= "Playlists with null service_playlist_id:\n";
                    foreach ($playlists as $playlist) {
                        $message .= "Playlist ID: {$playlist->id}\n";
                    }
                }
                if (!empty($duplicate_service_playlist_ids)) {
                    $message .= "Duplicate service_playlist_ids:\n";
                    foreach ($duplicate_service_playlist_ids as $record) {
                        $message .= "Service Playlist ID: {$record['service_playlist_id']}, Count: {$record['count']}\n";
                    }
                }
                throw new \Exception($message);
            }

            // We need another step to make sure config id is set and it is not null before altering the table with not-null config_id.
            $null_config_playlists = Playlists::findBySQL('config_id IS NULL');

            while (!empty($null_config_playlists)) {
                foreach ($null_config_playlists as $null_config_playlist) {
                    // Store config id with default config id.
                    $null_config_playlist->config_id = $default_config_id;
                    $null_config_playlist->store();
                }
                $null_config_playlists = Playlists::findBySQL('config_id IS NULL');
            }

            // Forbid playlist without related oc playlist
            // First drop foreign key constraint
            // Then change column to not null
            // Then add foreign key constraint again
            $db->exec('ALTER TABLE `oc_playlist`
                DROP FOREIGN KEY `oc_playlist_ibfk_1`,
                CHANGE COLUMN `config_id` `config_id` int NOT NULL,
                CHANGE COLUMN `service_playlist_id` `service_playlist_id` varchar(64) UNIQUE NOT NULL,
                ADD FOREIGN KEY `oc_playlist_ibfk_2` (`config_id`) REFERENCES `oc_config` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT'
            );

            // Forbid playlist video without related oc playlist entry
            $db->exec('ALTER TABLE `oc_playlist_video`
                CHANGE COLUMN `service_entry_id` `service_entry_id` int NOT NULL'
            );

            \SimpleOrMap::expireTableScheme();

            // Add playlists sync cronjob
            $scheduler = \CronjobScheduler::getInstance();

            if (!$task_id = \CronjobTask::findByFilename(self::CRONJOBS_DIR . 'opencast_sync_playlists.php')[0]->task_id) {
                $task_id =  $scheduler->registerTask(self::CRONJOBS_DIR . 'opencast_sync_playlists.php', true);
            }

            // add the new cronjobs
            if ($task_id) {
                $scheduler->cancelByTask($task_id);
                $scheduler->schedulePeriodic($task_id, -10);  // negative value means "every x minutes"
                \CronjobSchedule::findByTask_id($task_id)[0]->activate();
            }
        } catch (\Throwable $th) {
            throw new \Exception('Migration fehlgeschlagen: ' . $th->getMessage());
        }
    }

        /**
     * Extract oc playlist data from passed playlist
     *
     * @param Playlists $playlist
     * @return array opencast playlist representation
     */
    public static function getOcPlaylistData($playlist, $playlist_videos) {
        $owner_names = \User::findAndMapBySQL(function ($owner) {
                return $owner->getFullName();
            },
            "INNER JOIN oc_playlist_user_perms as ocpp
                ON (ocpp.user_id = auth_user_md5.user_id AND ocpp.playlist_id = ?)
                WHERE ocpp.perm = 'owner'",
            [$playlist->id]
        );

        $entries = [];
        foreach ($playlist_videos as $playlist_video) {
            if (!$playlist_video['episode']) continue;

            $entries[] = [
                'contentId' => $playlist_video['episode'],
                'type' => 'EVENT'
            ];
        }

        return [
            'title' => $playlist->title,
            'description' => '',
            'creator' => implode(', ', (array)$owner_names),
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
    public static function getPlaylistVideos($playlist)
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
     * Check if the playlists have been converted to OC playlists
     *
     * @return boolean
     */
    public static function isConverted()
    {
        $result = DBManager::get()->query("SHOW COLUMNS
            FROM `oc_playlist_video` LIKE 'service_entry_id'")->fetch();

        return $result['Null'] != 'YES';
    }
}
