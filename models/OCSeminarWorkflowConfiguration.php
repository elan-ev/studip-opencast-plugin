<?php

namespace Opencast\Models;

class OCSeminarWorkflowConfiguration extends \SimpleORMap
{
    protected static function configure($config = array())
    {
        $config['db_table'] = 'oc_seminar_workflow_configuration';

        parent::configure($config);
    }
}
