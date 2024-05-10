<?php

namespace Opencast\Models;

class PlaylistsUserPerms extends \SimpleORMap
{
    protected static function configure($config = [])
    {
        $config['db_table'] = 'oc_playlist_user_perms';

        $config['belongs_to']['user'] = [
            'class_name' => 'User',
            'foreign_key' => 'user_id',
        ];

        $config['belongs_to']['playlist'] = [
            'class_name' => 'Opencast\\Models\\Playlists',
            'foreign_key' => 'playlist_id',
        ];

        parent::configure($config);
    }
}
