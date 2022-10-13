<?php

namespace Opencast\Models;

class VideosShares extends \SimpleORMap
{
    protected static function configure($config = [])
    {
        $config['db_table'] = 'oc_video_shares';

        $config['belongs_to']['video'] = [
            'class_name' => 'Opencast\\Models\\Videos',
            'foreign_key' => 'video_id',
        ];

        parent::configure($config);
    }
}
