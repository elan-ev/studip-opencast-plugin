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

        if (empty($video)) {
            $this->set_layout(null);
            $this->render_action('video_not_found');
            return;
        }

        $series = OCSeminarSeries::findOneBySeries_id($video->series_id);

        if (empty($video)) {
            $this->error = _('Das Video wurde nicht gefunden, ist defekt oder momentan (noch) nicht verfügbar.');
        }

        if ($action == 'preview') {
            $customtool = $_REQUEST['image'];
        } else {
            $customtool = $this->getLtiCustomTool($episode_id, $action);
        }

        if (Request::get('embed')) {
            $endpoints = OCEndpoints::findByConfig_id($series->config_id);

            foreach ($endpoints as $endpoint) {
                if ($endpoint['service_type'] == 'search') {

                    $url = parse_url($endpoint['service_url']);

                    $oc_url = $url['scheme'] . '://'. $url['host']
                        . ($url['port'] ? ':' . $url['port'] : '');

                    $url = $oc_url . '/play/' . $episode_id;
                    break;
                }
            }

            if (empty($url)) {
                $this->set_layout(null);
                $this->render_action('video_not_found');
                return;
            }

            $this->redirect($url);
            return;
        }

        if (Context::getId()) {
            $lti = LtiHelper::getLaunchDataForCourse($series->config_id, Context::getId(), null, $customtool);
        } else {
            $lti = LtiHelper::getLaunchData($series->config_id, $customtool);
        }

        if (empty($lti) || empty($customtool)) {
            $this->error = _('Das Video wurde nicht gefunden, ist defekt oder momentan (noch) nicht verfügbar.');
        }

        // get correct endpoint for redirect type
        if ($action == 'video' || $action == 'preview') {
            $ltilink = self::getLtiLinkFor($lti, 'search');
        } else {
            $ltilink = self::getLtiLinkFor($lti, 'apievents');
        }

        $this->launch_data = $ltilink['launch_data'];
        $this->launch_url  = $ltilink['launch_url'];

        if (!empty($this->error)) {
            $this->set_layout(null);
            $this->assets_url = rtrim($this->plugin->getPluginUrl(), '/') . '/assets';
            $this->render_action('novideo');
            return;
        }
    }

    public function novideo_action()
    {
    }

    public function video_not_found_action()
    {
    }

    public function preview_action($episode_id)
    {
        global $user, $perm;

        $video      = OCSeminarEpisodes::findOneByEpisode_id($episode_id);
        $all_series = array_merge(
            OCSeminarSeries::getSeriesByUserMemberStatus($user->id, 'dozent'),
            OCSeminarSeries::getSeriesByUserMemberStatus($user->id, 'tutor'),
            OCSeminarSeries::getSeriesByUserMemberStatus($user->id, 'autor')
        );

        // check, if user has permissions in the course this video belongs to
        if ($perm->have_perm('root')
            || in_array($video->series_id, $all_series) !== false)
        {
            // get event from opencast
            $api_events = ApiEventsClient::getInstance();
            list ($httpCode, $api_event) = $api_events->getEpisode($episode_id, true);
            $event = ApiEventsClient::prepareEpisode($api_event);

            $image = $event['presentation_preview'];

            if (empty($image)) {
                $image = ($item['preview'] != false)
                    ? $item['preview']
                    : ''; // PluginEngine::getLink($plugin->getPluginURL() . '/images/default-preview.png';
            }

            list ($response, $httpCode, $mimetype) = $api_events->fileRequest($image);

            header('Content-Type: '. $mimetype);

            echo $response;
            die;
        }

        throw new \Exception('Access denied!');
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

        /**
     * Returns the custom_tool parameter based on the requested action, whether edit or annotation
     *
     * @param object $video video object
     * @param string $action the action
     * @return string $custom_tool
     */
    private function getLtiCustomTool($video_id, $action) {
        $custom_tool = '';

        switch ($action) {
            case 'annotation':
                $custom_tool = "/annotation-tool/index.html?id={$video_id}";
                break;

            case 'editor':
                $custom_tool = "/editor-ui/index.html?id={$video_id}";
                break;

            case 'video':
                $custom_tool = "/play/{$video_id}";
                break;
            default:
                $custom_tool = '/ltitools';
        }

        return $custom_tool;
    }

        /**
     * Get lti link for the passed endpoint
     *
     * @param mixed $lti
     * @param mixed $endpoint
     * @return void
     */
    private function getLtiLinkFor($lti, $endpoint)
    {
        // if there is only one node, use it for all calls
        if (sizeof($lti) == 1) {
            return reset($lti);
        }

        foreach ($lti as $entry) {
            if (in_array($endpoint, $entry['endpoints']) !== false) {
                return $entry;
            }
        }

        // if nothing has been found, at least try to use the first found link
        return reset($lti);
    }
}