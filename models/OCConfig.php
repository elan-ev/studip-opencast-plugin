<?php

namespace Opencast\Model;

class OCConfig extends SimpleORMap
{
    protected static function configure($config = array())
    {
        $config['db_table'] = 'oc_config';

        parent::configure($config);
    }
}
