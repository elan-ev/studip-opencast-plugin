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

        // Forbid playlist without related oc playlist
        $db->exec('ALTER TABLE `oc_playlist`
            CHANGE COLUMN `config_id` `config_id` int NOT NULL,
            CHANGE COLUMN `service_playlist_id` `service_playlist_id` varchar(64) UNIQUE NOT NULL'
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