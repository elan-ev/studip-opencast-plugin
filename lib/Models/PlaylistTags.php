<?php

namespace Opencast\Models;

class PlaylistTags extends \SimpleORMap
{
    protected static function configure($config = [])
    {
        $config['db_table'] = 'oc_playlist_tags';

        parent::configure($config);
    }
}
