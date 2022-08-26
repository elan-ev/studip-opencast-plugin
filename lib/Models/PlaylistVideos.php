<?php

namespace Opencast\Models;

class PlaylistVideos extends \SimpleORMap
{
    protected static function configure($config = [])
    {
        $config['db_table'] = 'oc_playlist_video';

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
        return self::findBySQL('playlist_id = ?', [$playlist_id]);
    }
}
