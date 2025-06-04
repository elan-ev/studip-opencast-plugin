<?php

namespace Opencast\Models;

class PlaylistVideosAuditLog extends UPMap
{
    const ACTION_ADD = 'add';
    const ACTION_DELETE = 'delete';
    const ACTION_RESTORE = 'restore';
    const ACTIONS = [
        self::ACTION_ADD,
        self::ACTION_DELETE,
        self::ACTION_RESTORE,
    ];

    protected static function configure($config = [])
    {
        $config['db_table'] = 'oc_playlist_videos_audit_log';

        $config['has_many']['videos'] = [
            'class_name'        => 'Opencast\\Models\\Videos',
            'assoc_foreign_key' => 'video_id',
        ];

        $config['has_many']['playlists'] = [
            'class_name' => 'Opencast\\Models\\Playlists',
            'assoc_foreign_key' => 'playlist_id',
        ];

        parent::configure($config);
    }

    public static function findById($id)
    {
        return self::findOneBySQL('id = ?', [$id]);
    }

    /**
     * Finds the latest action for a given playlist and video.
     *
     * @param int $playlist_id
     * @param int $video_id
     * @return PlaylistVideosAuditLog|null The latest action record or null if not found
     */
    public static function findLatestAction($playlist_id, $video_id)
    {
        return self::findOneBySQL(
            'playlist_id = ? AND video_id = ? ORDER BY mkdate DESC',
            [$playlist_id, $video_id]
        );
    }

    /**
     * Finds all distinct combinations of playlist_id and video_id in the audit log.
     *
     * @return array An array of associative arrays with keys 'playlist_id' and 'video_id'
     */
    public static function findAllCombinationEntries()
    {
        $query = "SELECT DISTINCT playlist_id, video_id
                FROM oc_playlist_videos_audit_log
                ORDER BY playlist_id, video_id";
        $entries = \DBManager::get()->query($query)->fetchAll(\PDO::FETCH_ASSOC);
        return $entries;
    }

    /**
     * Cleans up older entries in the audit log for a specific playlist and video,
     * keeping only the latest entry.
     *
     * @param int $playlist_id The ID of the playlist
     * @param int $video_id The ID of the video
     * @param int $latest_id The ID of the latest entry to keep
     * @return int|false The number of deleted entries or false on failure
     */
    public static function performOlderEntriesCleanup($playlist_id, $video_id, $latest_id)
    {
        // Delete all entries for the given playlist and video except the latest one!
        $query = "DELETE FROM oc_playlist_videos_audit_log
                    WHERE playlist_id = ? AND video_id = ? AND id != ?";
        $stmt = \DBManager::get()->prepare($query);
        $stmt->execute([$playlist_id, $video_id, $latest_id]);
        return $stmt->rowCount() ?? false;
    }

    /**
     * Checks if a video was deleted from a playlist.
     *
     * @param int $playlist_id The ID of the playlist
     * @param int $video_id The ID of the video
     * @return bool True if the video was deleted, false otherwise
     */
    public static function wasVideoDeleted($playlist_id, $video_id)
    {
        $log = self::findLatestAction($playlist_id, $video_id);
        return $log && $log->getValue('action') === self::ACTION_DELETE;
    }

    /**
     * Logs an action for a video in a playlist.
     *
     * @param int $playlist_id The ID of the playlist
     * @param int $video_id The ID of the video
     * @param string $action The action performed (add, delete, restore)
     * @throws \InvalidArgumentException If the action is not valid
     */
    public static function logAction($playlist_id, $video_id, $action)
    {
        if (!in_array($action, self::ACTIONS)) {
            throw new \InvalidArgumentException("Invalid audit log action: $action");
        }

        $log = new self();
        $log->setValue('playlist_id', $playlist_id);
        $log->setValue('video_id', $video_id);
        $log->setValue('action', $action);
        $log->store();
    }

    /**
     * Checks if a video was deleted from any course playlists.
     *
     * @param int $video_id The ID of the video
     * @param string $course_id The ID of the course
     * @return bool True if the video was deleted from any course playlist, false otherwise
     */
    public static function isRemovedFromCoursePlaylists($video_id, $course_id)
    {
        $playlists = PlaylistSeminars::findBySQL('seminar_id = ?', [$course_id]);
        foreach ($playlists as $playlist) {
            if (self::wasVideoDeleted($playlist->id, $video_id)) {
                return true;
            }
        }
        return false;
    }
}
