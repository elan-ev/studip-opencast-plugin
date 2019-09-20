<?php

namespace Opencast\Models;

class OCConfigPrecise extends \SimpleORMap
{
    protected static function configure($config = array())
    {
        $config['db_table'] = 'oc_config_precise';

        parent::configure($config);
    }
}
