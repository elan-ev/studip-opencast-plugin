<?php

namespace Opencast\Models;

class OCTos extends \SimpleORMap
{
    protected static function configure($config = [])
    {
        $config['db_table'] = 'oc_tos';

        parent::configure($config);
    }
}
