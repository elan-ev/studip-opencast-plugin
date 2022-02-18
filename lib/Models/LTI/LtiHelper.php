<?php

namespace Opencast\Models\LTI;

use Opencast\Models\Config;
use Opencast\Models\Endpoints;

/**
 * LTI Helper class to create launch data
 */
class LtiHelper
{
    /**
     * Returns an array of LtiLink-objects for every possible endpoint url
     * for the config with the passed config_id
     *
     * @param  int  $config_id   config to check
     *
     * @return Array            array of LtiLink
     */
    public static function getLtiLinks($config_id)
    {
        $links = [];
        $endpoints = Endpoints::findByConfig_id($config_id);

        foreach ($endpoints as $endpoint) {
            $url = parse_url($endpoint['service_url']);

            $lti_url = $url['scheme'] . '://'. $url['host']
                . ($url['port'] ? ':' . $url['port'] : '') . '/lti';

            if (!$links[$lti_url]) {
                $links[$lti_url] = [
                    'link' => new LtiLink(
                        $lti_url,
                        $config->settings['lti_consumerkey'],
                        $config->settings['lti_consumersecret']
                    ),
                    'endpoints' => [$endpoint->service_type]
                ];
            } else {
                $links[$lti_url]['endpoints'][] = $endpoint->service_type;
            }
        }

        return $links;
    }

    /**
     * Return basic launch data for every distinct endpoint url for the config
     * with the passed config_id
     *
     * @param  int  $config_id   config to check
     * @return Array             array of LtiLink
     */
    public static function getLaunchData($config_id)
    {
        $lti_links = [];

        foreach(self::getLtiLinks($config_id) as $lti) {
            $launch_data = $lti['link']->getBasicLaunchData();
            $signature   = $lti['link']->getLaunchSignature($launch_data);

            $launch_data['oauth_signature'] = $signature;

            $lti_links[] = [
                'launch_url'  => $lti['link']->getLaunchURL(),
                'launch_data' => $launch_data,
                'endpoints'   => $lti['endpoints'],
                'config_id'   => $config_id
            ];
        }

        return $lti_links;
    }

    /**
     * Return launch data with user and course for every distinct endpoint url
     * for the config with the passed config_id
     *
     * @param  int  $config_id                 config to check
     * @param  string $course_id               course_id to add to lti call
     * @param  string $user_id                 optional, defaults to $GLOBALS['user']->id;
     *
     * @return Array             array of LtiLink
     */
    public static function getLaunchDataForCourse($config_id, $course_id, $user_id = null)
    {
        global $user, $perm;

        if (!$user_id) {
            $user_id = $user->id;
        }

        $role = $perm->have_studip_perm('tutor', $course_id, $user_id)
            ? 'Instructor' : 'Learner';

        $lti_links = [];

        foreach(self::getLtiLinks($config_id) as $lti) {
            $lti['link']->setUser($user_id, $role, true);
            $lti['link']->setCourse($course_id);

            $launch_data = $lti['link']->getBasicLaunchData();
            $signature   = $lti['link']->getLaunchSignature($launch_data);

            $launch_data['oauth_signature'] = $signature;

            $lti_links[] = [
                'launch_url'  => $lti['link']->getLaunchURL(),
                'launch_data' => $launch_data,
                'endpoints'   => $lti['endpoints'],
                'config_id'   => $config_id
            ];
        }

        return $lti_links;
    }
}
