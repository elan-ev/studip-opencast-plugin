<?php
/**
 * @author          Jan-Frederik Leissner <jleissner@uos.de>
 * @copyright   (c) Authors
 * @version         1.0 (12:33)
 */

class OpencastLTI
{

    public static function get_roles_for_user($user_id){
        $courses_to_look_at = OpenCast::activated_in_courses();
        foreach ($courses_to_look_at as $course_id) {
            $course = Course::find($course_id);
        }
    }

    public static function get_possible_acl_lists_for_course($course_id)
    {
        //roles
        $role_instructor = static::role_instructor($course_id);
        $role_learner = static::role_learner($course_id);

        //basic
        $basic_acl = new AccessControlList('base');
        $basic_acl->add_ace(new AccessControlEntity('ROLE_ADMIN', 'read', true));
        $basic_acl->add_ace(new AccessControlEntity('ROLE_ADMIN', 'write', true));
        $basic_acl->add_ace(new AccessControlEntity($role_instructor, 'read', true));
        $basic_acl->add_ace(new AccessControlEntity($role_instructor, 'write', true));
        $basic_acl->add_ace(new AccessControlEntity($role_learner, 'write', false));

        //visible
        $visible_acl = new AccessControlList(static::acl_name($course_id, 'visible'));
        $visible_acl->add_acl($basic_acl);
        $visible_acl->add_ace(new AccessControlEntity($role_learner, 'read', true));

        //invisible
        $invisible_acl = new AccessControlList(static::acl_name($course_id, 'invisible'));
        $invisible_acl->add_acl($basic_acl);
        $invisible_acl->add_ace(new AccessControlEntity($role_learner, 'read', false));

        return [
            'visible'   => $visible_acl,
            'invisible' => $invisible_acl
        ];
    }

    public static function role_instructor($course_id)
    {
        return $course_id . '_Instructor';
    }

    public static function role_learner($course_id)
    {
        return $course_id . '_Learner';
    }

    public static function acl_name($course_id, $addendum)
    {
        return 'course_' . $course_id . '_' . $addendum;
    }
}