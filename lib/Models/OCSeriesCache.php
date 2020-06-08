<?php

namespace Opencast\Models;

class OCSeriesCache extends \SimpleORMap
{
    protected static function configure($config = array())
    {
        $config['db_table'] = 'oc_series_cache';

        parent::configure($config);
    }
}
