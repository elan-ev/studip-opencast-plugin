<?php

namespace Opencast\Models;

class VideoSync extends \SimpleORMap
{
    protected static function configure($config = [])
    {
        $config['db_table'] = 'oc_video_sync';

        parent::configure($config);
    }
}
