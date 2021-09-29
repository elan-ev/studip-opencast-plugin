<?php

namespace Opencast\Models;

class OCSeminarACLRefresh extends \SimpleORMap
{
    protected static function configure($config = array())
    {
        $config['db_table'] = 'oc_seminar_acl_refresh';
        parent::configure($config);
    }
}
