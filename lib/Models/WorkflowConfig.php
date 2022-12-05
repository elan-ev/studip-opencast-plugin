<?php

namespace Opencast\Models;

class WorkflowConfig extends \SimpleORMap
{
    protected static function configure($config = [])
    {
        $config['db_table'] = 'oc_workflow_config';

        parent::configure($config);
    }
}
