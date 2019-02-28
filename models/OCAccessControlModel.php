<?php
/**
 * @author          Jan-Frederik Leissner <jleissner@uos.de>
 * @copyright   (c) Authors
 * @version         1.0 (13:46)
 */

class OCAccessControlModel
{
    public static function get_acls_for_course($course_id)
    {
        $stmt = DBManager::get()->prepare('SELECT id, type, acl_id FROM `oc_access_control` WHERE course_id = ?');

        if ($stmt->execute([$course_id])) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return [];
    }

    public static function get_acls_for($type_name,$type_id){
        $stmt = DBManager::get()->prepare('SELECT acl_id FROM `oc_access_control` WHERE type = ? AND id = ?');

        if ($stmt->execute([$type_name,$type_id])) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return [];
    }

    public static function set_acl_for_course($id, $type, $course_id, $acl_id)
    {
        $stmt = DBManager::get()->prepare('INSERT INTO `oc_access_control` (id, type, acl_id, course_id) VALUES (?,?,?,?)');
        if (self::course_has_acl($id, $type, $course_id)) {
            $stmt = DBManager::get()->prepare('UPDATE `oc_access_control` SET id = ?, type = ?, acl_id = ? WHERE course_id = ?');
        }

        return $stmt->execute([$id, $type, $acl_id, $course_id]);
    }

    public static function remove_acls_for_course($course_id)
    {
        $stmt = DBManager::get()->prepare('DELETE FROM `oc_access_control` WHERE course_id = ?');
        return $stmt->execute([$course_id]);
    }

    public static function course_has_acl($id, $type, $course_id): bool
    {
        $acls_for_course = static::get_acls_for_course($course_id);
        foreach ($acls_for_course as $acl) {
            if ($acl['id'] == $id && $acl['type'] == $type){
                return true;
            }
        }
        return false;
    }

    public static function remove_acl_from_db($acl_id)
    {
        $stmt = DBManager::get()->prepare('DELETE FROM `oc_access_control` WHERE acl_id = ?');
        return $stmt->execute([$acl_id]);
    }
}