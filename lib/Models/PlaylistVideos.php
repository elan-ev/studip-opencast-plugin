<?php

namespace Opencast\Models;

class PlaylistVideos extends UPMap
{
    protected static function configure($config = [])
    {
        $config['db_table'] = 'oc_playlist_video';

        $config['has_one']['video'] = [
            'class_name'        => 'Opencast\\Models\\Videos',
            'assoc_foreign_key' => 'id',
            'foreign_key'       => 'video_id',
        ];

        parent::configure($config);
    }

    /**
     * Get records for a playlist
     *
     * @param int $playlist_id the playlist id
     *
     * @return array the record
     */
    public static function findByPlaylist_id($playlist_id)
    {
        return self::findBySQL('LEFT JOIN oc_video ov ON (ov.id = video_id) WHERE playlist_id = ? AND trashed = false', [$playlist_id]);
    }

    /**
     * Change orders of given records
     *
     * @param int $playlist_token the playlist token
     * @param array $order_list the new order
     *
     * @return array the record
     */
    public static function reorder($playlist_token, $order_list)
    {
        foreach ($order_list as $order => $video_token) {
            $playlist_video = self::findOneBySQL('LEFT JOIN oc_playlist AS ocp ON (ocp.id = playlist_id)
                LEFT JOIN oc_video AS ocv ON (ocv.id = video_id)
                WHERE ocp.token = ? and ocv.token = ?', [$playlist_token, $video_token]);

            $playlist_video->setValue('order', $order);
            $playlist_video->store();
        }
    }

    /**
     * @inheritdoc
     * Override delete method to log the deletion action!
     */
    public function delete()
    {
        PlaylistVideosAuditLog::logAction(
            $this->getValue('playlist_id'),
            $this->getValue('video_id'),
            PlaylistVideosAuditLog::ACTION_DELETE
        );
        return parent::delete();
    }

    /**
     * @inheritdoc
     * Override store method to log the store action!
     */
    public function store()
    {
        $playlist_id = $this->getValue('playlist_id');
        $video_id = $this->getValue('video_id');
        $action = PlaylistVideosAuditLog::ACTION_ADD;
        if (PlaylistVideosAuditLog::wasVideoDeleted($playlist_id, $video_id)) {
            $action = PlaylistVideosAuditLog::ACTION_RESTORE;
        }
        PlaylistVideosAuditLog::logAction($playlist_id, $video_id, $action);

        return parent::store();
    }
}
