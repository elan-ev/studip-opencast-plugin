<?php
/**
 * @author          Jan-Frederik Leissner <jleissner@uos.de>
 * @copyright   (c) Authors
 * @version         1.0 (12:33)
 */


namespace Opencast\LTI;

use Opencast\Models\OCAccessControl;
use Opencast\Models\OCConfig;
use Opencast\Models\OCSeminarSeries;
use Opencast\Models\OCSeminarEpisodes;

class OpencastLTI
{

    /**
     * Set the correct ACLs for the series and episodes in the passed course
     * @param [type] $course_id [description]
     */
    public static function setAcls($course_id)
    {
        // write the new ACLs to Opencast
        if ($mapping = self::generate_acl_mapping_for_course($course_id)) {
            $acls = self::mapping_to_defined_acls($mapping);
            self::apply_defined_acls($acls);
        }
    }

    public static function updateEpisodeVisibility($course_id)
    {
        // check currently set ACLs to update status in Stud.IP if necessary
        $series        = reset(OCSeminarSeries::getSeries($course_id));
        $search_client = \SearchClient::create($course_id);
        $api_client    = \ApiEventsClient::create($course_id);

        // check the opencast visibility for episodes and update Stud.IP settings
        foreach ($search_client->getEpisodes($series['series_id']) as $episode) {
            $vis = $api_client->getVisibilityForEpisode($series['series_id'], $episode->id);
            $entry = OCSeminarEpisodes::findOneBySQL(
                'series_id = ? AND episode_id = ?',
                [$series['series_id'], $episode->id]
            );

            if ($entry && $entry->visible != $vis) {
                $entry->visible = $vis;
                $entry->store();
            }
        }
    }

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
            $acls['e'][$episode_id] = [
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
        $series_list = OCSeminarSeries::getSeries($course_id);

        if (!$series_list) {
            return false;
        }

        $result = [
            's' => [],
            'e' => []
        ];

        $vis = \Config::get()->OPENCAST_HIDE_EPISODES
            ? 'invisible'
            : 'visible';

        foreach ($series_list as $series) {
            $result['s'][$series['series_id']][$course_id] = $vis;

            $course_model = new \OCCourseModel($course_id);
            $episodes = $course_model->getEpisodes();
            foreach ($episodes as $episode) {
                $result['e'][$episode['id']][$course_id] = $episode['visibility'];
            }
        }

        return $result;
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

        $base_acl = new \AccessControlList('base');
        $base_acl->add_ace(new \AccessControlEntity('ROLE_ADMIN', 'read', true));
        $base_acl->add_ace(new \AccessControlEntity('ROLE_ADMIN', 'write', true));
        $base_acl->add_ace(new \AccessControlEntity($role_i, 'read', true));
        $base_acl->add_ace(new \AccessControlEntity($role_i, 'write', true));

        $acl_visible = new \AccessControlList(static::generate_acl_name($course_id, 'visible'));
        $acl_visible->add_acl($base_acl);
        $acl_visible->add_ace(new \AccessControlEntity($role_l, 'read', true));
        // $acl_visible->add_ace(new \AccessControlEntity($role_l, 'write', false));

        $acl_invisible = new \AccessControlList(static::generate_acl_name($course_id, 'invisible'));
        $acl_invisible->add_acl($base_acl);

        $acl_free = new \AccessControlList(static::generate_acl_name($course_id, 'free'));
        $acl_free->add_acl($base_acl);
        $acl_free->add_ace(new \AccessControlEntity($role_l, 'read', true));
        // $acl_free->add_ace(new \AccessControlEntity($role_l, 'write', false));
        $acl_free->add_ace(new \AccessControlEntity('ROLE_ANONYMOUS', 'read', true));
        // $acl_free->add_ace(new \AccessControlEntity('ROLE_ANONYMOUS', 'write', false));


        return [
            'base'        => $base_acl,
            'visible'     => $acl_visible,
            'invisible'   => $acl_invisible,
            'free'        => $acl_free
        ];
    }

    public static function generate_combined_acls(array $course_ids, array $modes)
    {
        $name = static::generate_acl_name(uniqid(), 'mixed', 'combined');
        if (count($course_ids) == 1) {
            $name = static::generate_acl_name($course_ids[0], $modes[0], 'course');
        }
        $resulting_acl = new \AccessControlList($name);
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
        // TODO: use correct config!!!
        $acl_manager = \ACLManagerClient::getInstance();

        //$acls_to_remove = OCAccessControl::get_acls_for($target_type, $target_id);
        /*foreach ($acls_to_remove as $to_remove) {
            if ($acl_manager->removeACL($to_remove['acl_id'])) {
                OCAccessControl::remove_acl_from_db($to_remove['acl_id']);
            }
        }*/

        $created_acl = $acl_manager->createACL($acl);
        if ($acl_manager->applyACLto($target_type, $target_id, $created_acl->id)) {
            foreach ($courses as $course) {
                OCAccessControl::set_acl_for_course($target_id, $target_type, $course, $created_acl->id);
            }
        }
    }

    public static function getSearchUrl($course_id)
    {
        $config_id     = OCConfig::getConfigIdForCourse($course_id);
        $search_config = OCConfig::getConfigForService('search', $config_id);
        $config        = OCConfig::getConfigForCourse($course_id);

        $url = parse_url($search_config['service_url']);

        return $url['scheme'] . '://'. $url['host']
            . ($url['port'] ? ':' . $url['port'] : '') . '/lti';
    }
}
