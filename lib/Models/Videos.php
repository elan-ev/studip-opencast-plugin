<?php

namespace Opencast\Models;

class Videos extends \SimpleORMap
{
    protected static function configure($config = [])
    {
        $config['db_table'] = 'oc_video';

        $config['has_many']['perms'] = [
            'class_name' => 'Opencast\\Models\\VideosUserPerms',
            'assoc_foreign_key' => 'video_id',
        ];

        parent::configure($config);
    }

    public function getCleanedArray()
    {
        $data = $this->toArray();

        $data['chdate'] = ($data['chdate'] == '0000-00-00 00:00:00')
            ? 0 : \strtotime($data['chdate']);

        $data['mkdate'] = ($data['mkdate'] == '0000-00-00 00:00:00')
            ? 0 : \strtotime($data['mkdate']);

        return $data;
    }
    
    public static function findByPlaylist_token($playlist_token)
    {
        return self::findBySQL('LEFT JOIN oc_playlist_video AS ocpv
            ON (ocpv.video_id = oc_video.id)
            WHERE ocpv.playlist_id = ?', [$playlist_token]);
    }
}
