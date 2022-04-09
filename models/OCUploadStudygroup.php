<?php

namespace Opencast\Models;

class OCUploadStudygroup extends \SimpleORMap
{
    protected static function configure($config = [])
    {
        $config['db_table'] = 'oc_upload_studygroup';

        parent::configure($config);
    }
}
