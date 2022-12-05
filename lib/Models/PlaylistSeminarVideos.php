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
}
