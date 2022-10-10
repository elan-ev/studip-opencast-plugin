<?php

use Opencast\Models\Videos;
use Opencast\Models\LTI\LtiHelper;

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
        $video = Videos::findByToken($token);

        if (empty($video)) {
            throw new Error(_('Das Video kann nicht gefunden werden'), 404);
        }

        $perm = $video->getUserPerm();
        if (empty($perm) ||
            ($perm != 'owner' && $perm != 'write'))
        {
            throw new \AccessDeniedException();
        }

        $customtool = $this->getLtiCustomTool($video, $action);
        $lti = LtiHelper::getLaunchData($video->config_id, $customtool);
        if (empty($lti) || empty($customtool)) {
            throw new Error('Es fehlen Parameter!', 422);
        }

        // get correct endpoint for redirect type
        if ($action == 'video') {
            $ltilink = self::getLtiLinkFor($lti, 'search');
        } else {
            $ltilink = self::getLtiLinkFor($lti, 'apievents');
        }

        $this->launch_data = $ltilink['launch_data'];
        $this->launch_url  = $ltilink['launch_url'];
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
            $lti = LtiHelper::getLaunchDataForCourse($config_id, $context_id);

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
                $preview = $video->preview ? json_decode($video->preview, true) : null;
                if (!empty($preview) && isset($preview['has_previews']) && $preview['has_previews']) {
                    $custom_tool = "/editor-ui/index.html?id={$video->episode}";
                }
                break;

            case 'video':
                $preview = $video->preview ? json_decode($video->preview, true) : null;
                if (!empty($preview)) {
                    $custom_tool = "/paella/ui/watch.html?id={$video->episode}";
                }
                break;
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
                return $lti;
            }
        }

        // if nothing has been found, at least try to use the first found link
        return reset($lti);
    }
}