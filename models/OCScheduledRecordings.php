<?php

namespace Opencast\Models;

class OCScheduledRecordings extends \SimpleORMap
{
    protected static function configure($config = array())
    {
        $config['db_table'] = 'oc_scheduled_recordings';

        $config['has_one'] = [
           'date' => [
               'class_name'        => 'CourseDate',
               'assoc_foreign_key' => 'termin_id',
               'foreign_key'         => 'date_id'
           ]
       ];


        parent::configure($config);
    }
}
