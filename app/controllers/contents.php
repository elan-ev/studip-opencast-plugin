<?php

use Opencast\VersionHelper;

class ContentsController extends Opencast\Controller
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

    /**
     * This is the default action of this controller.
     */
    public function index_action()
    {
        VersionHelper::get()->activateContentNavigation();

        PageLayout::setTitle($this->_('Opencast Videos'));
        PageLayout::setBodyElementId('opencast-plugin');

        $this->studip_version = $this->getStudIPVersion();

        $languages = [];
        foreach ($GLOBALS['CONTENT_LANGUAGES'] as $lang => $content) {
            $languages[str_replace('_', '-', $lang)] = $content;
        }

        $this->languages = json_encode($languages);

    }
}
