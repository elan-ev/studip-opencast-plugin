<?php

namespace Opencast\Model;

class OCSeminarWorkflowConfiguration extends SimpleORMap
{
    protected static function configure($config = array())
    {
        $config['db_table'] = 'oc_seminar_series';
        parent::configure($config);
    }
}
