<?php

namespace Opencast\Models;

class PlaylistSeminars extends \SimpleORMap
{
    protected static function configure($config = [])
    {
        $config['db_table'] = 'oc_playlist_seminar';

        parent::configure($config);
    }
}
