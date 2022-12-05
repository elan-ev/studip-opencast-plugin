<?php

namespace Opencast\Models;

class VideosArchive extends UPMap
{
    protected static function configure($config = [])
    {
        $config['db_table'] = 'oc_video_archive';

        parent::configure($config);
    }
}
