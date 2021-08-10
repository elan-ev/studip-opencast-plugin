<?php

namespace Opencast\Models;

class OCResources extends \SimpleORMap
{
    protected static function configure($config = array())
    {
        $config['db_table'] = 'oc_resources';

        parent::configure($config);
    }
}
