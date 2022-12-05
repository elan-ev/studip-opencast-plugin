<?php

namespace Opencast\Models;

class Tags extends \SimpleORMap
{
    protected static function configure($config = [])
    {
        $config['db_table'] = 'oc_tags';

        parent::configure($config);
    }
}
