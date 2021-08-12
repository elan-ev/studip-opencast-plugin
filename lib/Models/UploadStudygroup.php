<?php

namespace Opencast\Models;

class UploadStudygroup extends \SimpleORMap
{
    protected static function configure($config = array())
    {
        $config['db_table'] = 'oc_upload_studygroup';

        parent::configure($config);
    }
}
