<?php

namespace Opencast\Models;

class VideoTags extends \SimpleORMap
{
    protected static function configure($config = [])
    {
        $config['db_table'] = 'oc_video_tags';

        parent::configure($config);
    }
}
