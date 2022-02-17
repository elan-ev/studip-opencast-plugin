<?php

namespace Opencast\Models\LTI;

use Opencast\Models\Config;

/**
 * LTI Helper class to create launch data
 */
class LtiHelper
{
    public static function getLtiLink($config_id)
    {
        // return lti data to test lti connection
        $search_config = Config::getConfigForService('search', $config_id);
        $url = parse_url($search_config['service_url']);

        $search_url = $url['scheme'] . '://'. $url['host']
            . ($url['port'] ? ':' . $url['port'] : '') . '/lti';

        return new LtiLink(
            $search_url,
            $config->settings['lti_consumerkey'],
            $config->settings['lti_consumersecret']
        );
    }

    public static function getLaunchData($config_id)
    {

        $lti_link    = self::getLtiLink($config_id);
        $launch_data = $lti_link->getBasicLaunchData();
        $signature   = $lti_link->getLaunchSignature($launch_data);

        $launch_data['oauth_signature'] = $signature;

        $lti = [
            'launch_url'  => $lti_link->getLaunchURL(),
            'launch_data' => $launch_data
        ];

        return $lti;
    }

    public static function getLaunchDataForCourse($config_id, $course_id, $user_id = null)
    {
        global $user, $perm;

        if (!$user_id) {
            $user_id = $user->id;
        }

        $lti_link = self::getLtiLink($config_id);

        $role = $perm->have_studip_perm('tutor', $course_id, $user_id)
            ? 'Instructor' : 'Learner';

        $lti_link->setUser($user_id, $role, true);
        $lti_link->setCourse($course_id);

        $launch_data = $lti_link->getBasicLaunchData();
        $signature   = $lti_link->getLaunchSignature($launch_data);

        $launch_data['oauth_signature'] = $signature;

        $lti = [
            'launch_url'  => $lti_link->getLaunchURL(),
            'launch_data' => $launch_data
        ];

        return $lti;
    }
}
