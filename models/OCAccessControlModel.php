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
        $stmt = DBManager::get()->prepare('SELECT acl_visible_id, acl_invisible_id FROM `oc_access_control` WHERE course_id = ?');

        if ($stmt->execute([$course_id])) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return [];
    }

    public static function set_acls_for_course($course_id, $v_acl_id, $i_acl_id)
    {
        $stmt = DBManager::get()->prepare('INSERT INTO `oc_access_control` (acl_visible_id,acl_invisible_id,course_id) VALUES (?,?,?)');
        if (self::course_has_acls($course_id)) {
            $stmt = DBManager::get()->prepare('UPDATE `oc_access_control` SET acl_visible_id = ?, acl_invisible_id = ? WHERE course_id = ?');
        }

        return $stmt->execute([$v_acl_id, $i_acl_id, $course_id]);
    }

    public static function remove_acls_for_course($course_id)
    {
        $stmt = DBManager::get()->prepare('DELETE FROM `oc_access_control` WHERE course_id = ?');
        return $stmt->execute([$course_id]);
    }

    public static function course_has_acls($course_id): bool
    {
        return count(static::get_acls_for_course($course_id)) > 0;
    }

    function create_acls_for_course($course_id, $override = false)
    {
        $acl_manager = ACLManagerClient::getInstance(course_id);

        if (!OCAccessControlModel::course_has_acls($course_id) || $override) {
            $acls = OpencastLTI::generate_standard_acls($course_id);
            $visible = $acl_manager->createACL($acls['visible']);
            $invisible = $acl_manager->createACL($acls['invisible']);
            var_dump($visible);
            var_dump($invisible);
            if (!$visible || !$invisible) {
                return false;
            }

            return OCAccessControlModel::set_acls_for_course($course_id, $visible['id'], $invisible['id']);
        }

        return false;
    }
}