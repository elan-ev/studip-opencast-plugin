<?php

namespace Opencast\Models;

class OCAccessControl extends \SimpleORMap
{
    protected static function configure($config = array())
    {
        $config['db_table'] = 'oc_access_control';

        parent::configure($config);
    }

    public static function get_acls_for_course($course_id)
    {
        return \SimpleCollection::createFromArray(
            self::findByCourse_id($course_id)
        )->toArray();
    }

    public static function get_acls_for($type_name, $type_id)
    {
        return \SimpleCollection::createFromArray(
            self::findBySql('type = ? AND id = ?', [$type_name,$type_id])
        )->toArray();

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

    public static function remove_acls_for_course($course_id)
    {
        return self::deleteBySql('course_id = ?', [$course_id]);
    }

    public static function remove_acl_from_db($acl_id)
    {
        return self::deleteBySql('acl_id = ?', [$acl_id]);
    }
}
