<?php

namespace Opencast\Models;

class OCSeminarWorkflows extends \SimpleORMap
{
    protected static function configure($config = array())
    {
        $config['db_table'] = 'oc_seminar_workflows';
        parent::configure($config);
    }
}
