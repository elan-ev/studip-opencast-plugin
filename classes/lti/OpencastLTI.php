<?php
/**
 * @author          Jan-Frederik Leissner <jleissner@uos.de>
 * @copyright   (c) Authors
 * @version         1.0 (12:33)
 */

class OpencastLTI
{

    public static function generate_all_acls()
    {
        $courses = OpenCast::activated_in_courses();
        $result = [
            's' => [],
            'e' => []
        ];
        foreach ($courses as $course) {
            $acls = static::generate_course_acls($course);
            $result['s'] += $acls['s'];
            $result['e'] += $acls['e'];
        }

        return $result;
    }

    public static function generate_course_acls($course_id)
    {
        $acls = static::generate_standard_acls($course_id);

        $series_visible = OCSeriesModel::getVisibility($course_id)['visibility'] == 'visible';
        $result = [
            's' => [],
            'e' => []
        ];

        $result['s'][$course_id] = ($series_visible ? $acls['visible'] : $acls['invisible']);

        if ($series_visible) {
            $course_model = new OCCourseModel($course_id);
            $episodes = $course_model->getEpisodes();
            foreach ($episodes as $episode) {
                if ($episode['visibility'] == 'false') {
                    $result['e'][$episode['id']] = $acls['invisible'];
                } else {
                    $result['e'][$episode['id']] = 'none';
                }
            }
        }

        return $result;
    }

    public static function generate_lti_launch_data($user_id, $course_id, LTIResourceLink $resource_link, $privacy = false)
    {
        $user = User::find($user_id);
        $course = Course::find($course_id);

        $launch_data = [
            'lti_message_type'                 => 'basic-lti-launch-request',
            'lti_version'                      => 'LTI-1p0',
            'resource_link_id'                 => $resource_link->id,
            'resource_link_title'              => $resource_link->title,
            'resource_link_description'        => $resource_link->description,
            'user_id'                          => $user_id,
            'roles'                            => [],
            'lis_person_name_full'             => $user->getFullName(),
            'lis_person_name_given'            => $user->vorname,
            'lis_person_name_family'           => $user->nachname,
            'lis_person_contact_email_primary' => $user->email,
            'context_id'                       => $course_id,
            'context_type'                     => 'CourseSection',
            'context_title'                    => $course->name,
            'contect_label'                    => $course->veranstaltungsnummer
        ];

        if ($privacy) {
            $private_text = 'private';
            $privatize = [
                'lis_person_name_full',
                'lis_person_name_given',
                'lis_person_name_family',
                'lis_person_contact_email_primary'
            ];
            foreach ($privatize as $key){
                $launch_data[$key] = $private_text;
            }
        }

        foreach ($course->members as $member) {
            if ($member->user_id == $user_id) {
                $launch_data['roles'][] = ($member->status == 'dozent' ? 'Instructor' : 'Learner');
                break;
            }
        }

        return $launch_data;
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

    private static function generate_standard_acls($course_id)
    {
        $role_l = static::role_learner($course_id);
        $role_i = static::role_instructor($course_id);

        $base_acl = new AccessControlList('base');
        $base_acl->add_ace(new AccessControlEntity('ROLE_ADMIN', 'read', true));
        $base_acl->add_ace(new AccessControlEntity('ROLE_ADMIN', 'write', true));
        $base_acl->add_ace(new AccessControlEntity($role_i, 'read', true));
        $base_acl->add_ace(new AccessControlEntity($role_i, 'write', true));
        $base_acl->add_ace(new AccessControlEntity($role_l, 'write', false));

        $acl_visible = new AccessControlList(static::acl_name($course_id, 'visible'));
        $acl_visible->add_acl($base_acl);
        $acl_visible->add_ace(new AccessControlEntity($role_l, 'read', true));

        $acl_invisible = new AccessControlList(static::acl_name($course_id, 'invisible'));
        $acl_invisible->add_acl($base_acl);
        $acl_invisible->add_ace(new AccessControlEntity($role_l, 'read', false));

        return [
            'base'      => $base_acl,
            'visible'   => $acl_visible,
            'invisible' => $acl_invisible
        ];
    }
}

class LTIResourceLink
{
    public $id;
    public $title;
    public $description;

    public function __construct($id, $title, $description)
    {
        $this->id = $id;
        $this->title = $title;
        $this->description = $description;
    }

    public static function generate_link($title, $description)
    {
        return new LTIResourceLink(uniqid('ocplugin'), $title, $description);
    }

}