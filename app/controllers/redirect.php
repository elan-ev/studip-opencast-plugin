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

        $lti = $lti[0];
        $this->launch_data =  $lti['launch_data'];
        $this->launch_url =  $lti['launch_url'];
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
                    $custom_tool = $publication['annotation_tool'];
                }
                break;
            
            case 'editor':
                $preview = $video->preview ? json_decode($video->preview, true) : null;
                if (!empty($preview) && isset($preview['has_previews']) && $preview['has_previews']) {
                    $custom_tool = "/editor-ui/index.html?id={$video->episode}";
                    // $custom_tool = "/admin-ng/index.html#!/events/events/{$video->episode}/tools/editor";
                }
                break;
        }
        return $custom_tool;
    }
}