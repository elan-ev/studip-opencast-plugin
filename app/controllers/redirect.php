<?php

use Opencast\Models\Videos;
use Opencast\Models\VideosShares;
use Opencast\Models\LTI\LtiHelper;
use Opencast\Models\REST\ApiEventsClient;

class RedirectController extends Opencast\Controller
{
    public function __construct($dispatcher)
    {
        parent::__construct($dispatcher);

        $this->plugin = $dispatcher->current_plugin;

        PageLayout::setHelpKeyword('Opencast');
    }

    /**
     * Common code for all actions: set default layout and page title.
     */
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        $this->user_id = $GLOBALS['user']->id;
    }

    public function perform_action($action, $token)
    {
        $video = null;
        $video_share = null;
        if ($action == 'share') {
            $video_share = VideosShares::findByToken($token);
            $video = Videos::findById($video_share->video_id);
        } else {
            $video = Videos::findByToken($token);
        }

        if (empty($video)) {
            $this->error = _('Das Video wurde nicht gefunden, ist defekt oder momentan (noch) nicht verfügbar.');
        } else if ($video->trashed) {
            $this->error = _('Das Video wurde zur Löschung markiert und kann daher nicht abgerufen werden.');
        }

        /*
        if (!$video->havePerm('write'))
        {
            throw new \AccessDeniedException();
        }
        */

        $customtool = $this->getLtiCustomTool($video, $action);
        $lti = LtiHelper::getLaunchData($video->config_id, $customtool, $video_share);
        if (empty($lti) || empty($customtool)) {
            $this->error = _('Das Video wurde nicht gefunden, ist defekt oder momentan (noch) nicht verfügbar.');
        }

        // get correct endpoint for redirect type
        if ($action == 'video' || $action == 'share') {
            $ltilink = self::getLtiLinkFor($lti, 'play');
        } else {
            $ltilink = self::getLtiLinkFor($lti, 'apievents');
        }

        $this->launch_data = $ltilink['launch_data'];
        $this->launch_url  = $ltilink['launch_url'];

        if (!empty($this->error)) {
            $this->assets_url = rtrim($this->plugin->getPluginUrl(), '/') . '/assets';
        }

        $this->set_layout(null);
    }

    public function download_action($token, $type, $index)
    {
        $video = null;
        $video = Videos::findByToken($token);

        if (empty($video)) {
            $this->error = _('Das Video wurde nicht gefunden, ist defekt oder momentan (noch) nicht verfügbar.');
        } else if ($video->trashed) {
            $this->error = _('Das Video wurde zur Löschung markiert und kann daher nicht abgerufen werden.');
        }

        if ($video->havePerm('read')) {

            $publication = $video->publication? json_decode($video->publication, true) : null;
            if (!empty($publication) && isset($publication['downloads'][$type][$index]['url'])) {
                $url = $publication['downloads'][$type][$index]['url'];

                $api_events = ApiEventsClient::getInstance($video->config_id);
                $response = $api_events->fileRequest($url);

                header('Content-Type: '. $response['mimetype']);

                echo $response['body'];
                die;
            }
        }

        throw new \AccessDeniedException();
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
    private function getLtiCustomTool($video, $action) {
        $custom_tool = '';

        switch ($action) {
            case 'annotation':
                $publication = $video->publication ? json_decode($video->publication, true) : null;
                if (!empty($publication) && $publication['annotation_tool']) {
                    $custom_tool = "/annotation-tool/index.html?id={$video->episode}";
                }
                break;

            case 'editor':
                $custom_tool = "/editor-ui/index.html?id={$video->episode}";
                break;

            case 'share':
            case 'video':
                $video->views += 1;
                $video->store();
                $custom_tool = "/play/{$video->episode}";
                break;
            case 'livestream':
                if (!empty($video->livestream_link)) {
                    $custom_tool = $video->livestream_link;
                }
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

    /**
     * Load preview image from opencast and show it
     *
     * @param String $episode_id
     *
     * @return void
     */
    public function preview_action($token)
    {
        $this->set_layout(null);

        $video = Videos::findByToken($token);

        if (!$video->havePerm('read')) {
            throw new \Exception('Access denied!');
        }

        // get preview image
        $api_events = ApiEventsClient::getInstance($video->config_id);

        $image = $video->preview ?
            : URLHelper::getURL($this->plugin->getPluginUrl() . '/assets/images/default-preview.png');

        $response = $api_events->fileRequest($image);

        header('Content-Type: '. $response['mimetype']);

        echo $response['body'];
        die;
    }
}
