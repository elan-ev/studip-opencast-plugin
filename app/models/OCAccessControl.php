<?php

namespace Opencast\Models;

class OCAccessControl extends \SimpleORMap
{
    protected static function configure($config = [])
    {
        $config['db_table'] = 'oc_access_control';

        parent::configure($config);
    }

    public static function set_acl_for_course($id, $type, $course_id, $acl_id)
    {
        $acl = self::findOneBySql(
            'id = ? AND type = ? AND course_id = ?',
            [$id, $type, $course_id]
        );

        if (!$acl) {
            $acl = new self();
        }

        if (!is_null($acl->acl_id) && $acl->acl_id != $acl_id) {
            $acl->setData(compact('id', 'type', 'acl_id', 'course_id'));
            return $acl->store();
        }

        return true;
    }
}
