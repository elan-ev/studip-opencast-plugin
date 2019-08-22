<?php

namespace Opencast\Model;

class OCEndpoints extends SimpleORMap
{
    protected static function configure($config = array())
    {
        $config['db_table'] = 'oc_endpoints';

        parent::configure($config);
    }
}
