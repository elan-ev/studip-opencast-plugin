<?php

use Opencast\Models\OCSeminarEpisodes;
use Opencast\Models\OCSeminarSeries;
use Opencast\Models\OCEndpoints;
use Opencast\LTI\LtiHelper;

class RedirectController extends OpencastController
{
    public function perform_action($action, $episode_id)
    {
        $video = OCSeminarEpisodes::findOneByEpisode_id($episode_id);

        if ($action !== 'video' || empty($video)) {
            // return from this route and show template containing the default oc preview image
            $this->set_layout(null);
            return;
        }

        $series = OCSeminarSeries::findOneBySeries_id($video->series_id);
        $endpoint = OCEndpoints::findOneBySQL("config_id = ? AND service_type = 'search'", [$series->config_id]);

        $url = parse_url($endpoint['service_url']);

        $play_url = $url['scheme'] . '://'. $url['host']
            . ($url['port'] ? ':' . $url['port'] : '') . "/play/{$episode_id}";

        $this->redirect($play_url);
    }


    /**
     * Directly redirect to passed LTI endpoint
     *
     * @param [type] $launch_url
     * @param [type] $launch_data
     *
     * @return void
     */
    public function authenticate_action($num)
    {
        $course_id = Context::getId();
        $config_id = Request::int('config_id');

        if (empty($config_id)) {
            throw new \Exception('missing or wrong config id!');
        }

        if ($course_id) {
            $lti = LtiHelper::getLaunchDataForCourse($config_id, $course_id);

        } else {
            $lti = LtiHelper::getLaunchData($config_id);
        }

        if (empty($lti[$num])) {
            throw new \Exception('error creating lti call');
        }

        $this->launch_data = $lti[$num]['launch_data'];
        $this->launch_url  = $lti[$num]['launch_url'];
    }
}