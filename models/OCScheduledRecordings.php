<?php

namespace Opencast\Model;

class OCScheduledRecordings extends SimpleORMap
{
    protected static function configure($config = array())
    {
        $config['db_table'] = 'oc_scheduled_recordings';

        parent::configure($config);
    }
}
