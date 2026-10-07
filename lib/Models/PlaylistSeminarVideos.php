<?php

namespace Opencast\Models;

class PlaylistSeminarVideos extends \SimpleORMap
{
    protected static function configure($config = [])
    {
        $config['db_table'] = 'oc_playlist_seminar_video';

        $config['belongs_to']['video'] = [
            'class_name' => 'Opencast\\Models\\Videos',
            'foreign_key' => 'video_id',
        ];

        parent::configure($config);
    }

    /**
     * Return the course role scopes in which this event's scheduled visibility
     * has not started yet.
     *
     * The returned course ids identify learner roles on this event's Opencast
     * ACL; this does not change permissions for the course itself.
     *
     * @param Videos $video
     * @return array
     */
    public static function getPendingVisibilityCourseIds(Videos $video)
    {
        $query = 'SELECT DISTINCT ops.seminar_id FROM oc_playlist_seminar_video AS opsv'.
                 ' INNER JOIN oc_playlist_seminar AS ops ON (ops.id = opsv.playlist_seminar_id)'.
                 ' INNER JOIN oc_playlist_video AS opv ON (opv.playlist_id = ops.playlist_id AND opv.video_id = opsv.video_id)'.
                 ' WHERE opsv.video_id = :video_id AND opsv.visible_timestamp > NOW()';

        $stmt = \DBManager::get()->prepare($query);
        $stmt->execute([':video_id' => $video->id]);

        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Activate scheduled visibility settings whose timestamp has been reached.
     *
     * @param Videos $video
     * @return int number of updated settings
     */
    public static function activateScheduledVisibility(Videos $video)
    {
        $stmt = \DBManager::get()->prepare(
            'UPDATE oc_playlist_seminar_video'.
            ' SET visibility = "visible", visible_timestamp = NULL'.
            ' WHERE video_id = :video_id'.
            ' AND visible_timestamp IS NOT NULL'.
            ' AND visible_timestamp <= NOW()'
        );
        $stmt->execute([':video_id' => $video->id]);

        return $stmt->rowCount();
    }
}
