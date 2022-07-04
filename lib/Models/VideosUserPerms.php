<?php

namespace Opencast\Models;

class VideosUserPerms extends \SimpleORMap
{
    protected static function configure($config = [])
    {
        $config['db_table'] = 'oc_video_user_perms';

        $config['belongs_to']['user'] = [
            'class_name' => 'User',
            'foreign_key' => 'user_id',
        ];

        parent::configure($config);
    }
}
