<?php

namespace Opencast\Models;

class SeminarWorkflowConfiguration extends \SimpleORMap
{
    protected static function configure($config = [])
    {
        $config['db_table'] = 'oc_seminar_workflow_configuration';
        parent::configure($config);
    }

    public static function getWorkflowForCourse($course_id)
    {
        $defaults  = self::findBySeminar_id('default_workflow');
        $workflows = self::findBySeminar_id($course_id);

        $ret = [];

        foreach($defaults as $default) {
            $ret[$default->target] = $default->workflow_id;
        }

        foreach ($workflows as $workflow) {
            $ret[$workflow->target] = $workflow->workflow_id;
        }

        return $ret;
    }
}
