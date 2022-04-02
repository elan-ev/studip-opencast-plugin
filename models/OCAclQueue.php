<?php

namespace Opencast\Models;

class OCAclQueue extends \SimpleORMap
{
    protected static function configure($config = array())
    {
        $config['db_table'] = 'oc_acl_queue';
        parent::configure($config);
    }
}
