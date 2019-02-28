<?php
/**
 * @author          Jan-Frederik Leissner <jleissner@uos.de>
 * @copyright   (c) Authors
 * @version         1.0 (12:33)
 */

class OpencastLTI
{

    public static function apply_defined_acls($defined_acls)
    {
        foreach ($defined_acls['s'] as $series_id => $setting) {
            self::apply_acl_to_courses($setting['acl'], $setting['courses'], $series_id, 'series');
        }
        foreach ($defined_acls['e'] as $episode_id => $setting) {
            self::apply_acl_to_courses($setting['acl'], $setting['courses'], $episode_id, 'episode');
        }
    }

    public static function mapping_to_defined_acls($mapping)
    {
        $acls = [
            's' => [],
            'e' => []
        ];

        foreach ($mapping['s'] as $series_id => $setting) {
            $acls['s'][$series_id] = [
                'courses' => array_keys($setting),
                'acl'     => static::generate_combined_acls(array_keys($setting), array_values($setting))
            ];
        }
        foreach ($mapping['e'] as $episode_id => $setting) {
            $acls[$series_id] = [
                'courses' => array_keys($setting),
                'acl'     => static::generate_combined_acls(array_keys($setting), array_values($setting))
            ];
        }

        return $acls;
    }

    public static function generate_acl_mapping_for_series($series_id)
    {
        $courses = OCSeriesModel::getCoursesForSeries($series_id);
        $refined = [];
        foreach ($courses as $course) {
            $refined[] = $course['seminar_id'];
        }

        return self::generate_complete_acl_mapping($refined);
    }

    public static function generate_complete_acl_mapping($courses = [])
    {
        if (count($courses) == 0) {
            $courses = OpenCast::activated_in_courses();
        }

        $result = [
            's' => [],
            'e' => []
        ];
        foreach ($courses as $course) {
            $acls = static::generate_acl_mapping_for_course($course);
            $result = array_merge_recursive($result, $acls);
        }

        return $result;
    }

    public static function generate_acl_mapping_for_course($course_id)
    {
        $series_list = OCSeriesModel::getConnectedSeriesDB($course_id);
        $result = [
            's' => [],
            'e' => []
        ];

        foreach ($series_list as $series) {
            $result['s'][$series['series_id']][$course_id] = $series['visibility'];

            if ($series['visibility'] == 'visible') {
                $course_model = new OCCourseModel($course_id);
                $episodes = $course_model->getEpisodes();
                foreach ($episodes as $episode) {
                    if ($episode['visibility'] == 'false') {
                        $result['e'][$episode['id']][$course_id] = 'invisible';
                    }
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
            foreach ($privatize as $key) {
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

    public static function generate_standard_acls($course_id)
    {
        $role_l = static::role_learner($course_id);
        $role_i = static::role_instructor($course_id);

        $base_acl = new AccessControlList('base');
        $base_acl->add_ace(new AccessControlEntity('ROLE_ADMIN', 'read', true));
        $base_acl->add_ace(new AccessControlEntity('ROLE_ADMIN', 'write', true));
        $base_acl->add_ace(new AccessControlEntity($role_i, 'read', true));
        $base_acl->add_ace(new AccessControlEntity($role_i, 'write', true));
        $base_acl->add_ace(new AccessControlEntity($role_l, 'write', false));

        $acl_visible = new AccessControlList(static::generate_acl_name($course_id, 'visible'));
        $acl_visible->add_acl($base_acl);
        $acl_visible->add_ace(new AccessControlEntity($role_l, 'read', true));

        $acl_invisible = new AccessControlList(static::generate_acl_name($course_id, 'invisible'));
        $acl_invisible->add_acl($base_acl);
        $acl_invisible->add_ace(new AccessControlEntity($role_l, 'read', false));

        return [
            'base'      => $base_acl,
            'visible'   => $acl_visible,
            'invisible' => $acl_invisible
        ];
    }

    public static function generate_combined_acls(array $course_ids, array $modes)
    {
        $name = static::generate_acl_name(uniqid(), 'mixed', 'combined');
        if (count($course_ids) == 1) {
            $name = static::generate_acl_name($course_ids[0], $modes[0], 'course');
        }
        $resulting_acl = new AccessControlList($name);
        for ($index = 0; $index < count($course_ids); $index++) {
            $course_id = $course_ids[$index];
            $mode = $modes[$index];

            $resulting_acl->add_acl(static::generate_standard_acls($course_id)[$mode]);
        }

        return $resulting_acl;
    }

    public static function generate_acl_name($base_id, $mode, $base = 'course')
    {
        return $base . '_' . $base_id . '_' . $mode;
    }

    public static function parse_acl_name($acl_name)
    {
        $content = explode('_', $acl_name);

        return [
            'base'    => $content[0],
            'base_id' => $content[1],
            'mode'    => $content[2]
        ];
    }

    /**
     * @param $acl_manager
     * @param $acl
     * @param $series_id
     *
     * @return array
     */
    public static function apply_acl_to_courses($acl, $courses, $target_id, $target_type)
    {
        $acl_manager = ACLManagerClient::getInstance();

        $acls_to_remove = OCAccessControlModel::get_acls_for($target_type,$target_id);
        foreach ($acls_to_remove as $to_remove){
            if($acl_manager->removeACL($to_remove['acl_id'])){
                OCAccessControlModel::remove_acl_from_db($to_remove['acl_id']);
            }
        }

        $created_acl = $acl_manager->createACL($acl);
        if ($acl_manager->applyACLto($target_type, $target_id, $created_acl->id)) {
            foreach ($courses as $course) {
                OCAccessControlModel::set_acl_for_course($target_id, $target_type, $course, $created_acl->id);
            }
        }
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