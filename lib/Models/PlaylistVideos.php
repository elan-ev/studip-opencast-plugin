<?php

namespace Opencast\Models;

class PlaylistVideos extends \SimpleORMap
{
    protected static function configure($config = [])
    {
        $config['db_table'] = 'oc_playlist_video';

        parent::configure($config);
    }
}
