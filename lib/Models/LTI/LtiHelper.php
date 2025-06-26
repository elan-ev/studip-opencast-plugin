<?php

namespace Opencast\Models\LTI;

use Opencast\Models\Config;
use Opencast\Models\Endpoints;
use Opencast\Providers\Perm;
use Opencast\Models\REST\Config as RESTConfig;

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
        $config    = Config::find($config_id);

        list($maintenance_active, $read_only) = $config->isUnderMaintenance();

        if ($maintenance_active && !$read_only) {
            return [];
        }

        $endpoints = Endpoints::findByConfig_id($config_id);

        foreach ($endpoints as $endpoint) {
            // skip 'services' endpoints
            if ($endpoint->service_type == 'services') {
                continue;
            }

            // In read-only maintenance mode, we only allow engage node services!
            if ($read_only && !in_array($endpoint->service_type, RESTConfig::ENGAGE_NODE_SERVICE_TYPES)) {
                continue;
            }

            $url = parse_url($endpoint['service_url']);

            $lti_url = $url['scheme'] . '://'. $url['host']
                . (isset($url['port']) ? ':' . $url['port'] : '') . '/lti';

            if (empty($links[$lti_url])) {
                $links[$lti_url] = [
                    'link' => new LtiLink(
                        $lti_url,
                        $config->settings['lti_consumerkey'],
                        $config->settings['lti_consumersecret']
                    ),
                    'endpoints'   => [$endpoint->service_type],
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
     * @param  string  $custom_tool the custom tool parameter
     * @param  object  $video_share  the video share object
     * @return Array             array of LtiLink
     */
    public static function getLaunchData($config_id, $custom_tool = '/ltitools', $video_share = null)
    {
        global $user;

        $lti_links = [];

        foreach(self::getLtiLinks($config_id) as $lti) {
            if (!empty($video_share)) {
                $lti['link']->setSharedUser($video_share);
            } else {
                $lti['link']->setUser($user->id, 'Instructor', true);
            }

            if (!empty($custom_tool)) {
                $lti['link']->addCustomParameter('tool', urlencode($custom_tool));
            }
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

        $role = Perm::uploadAllowed($course_id, $user_id)
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
