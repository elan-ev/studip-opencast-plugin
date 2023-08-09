<?php
/*
 * course.php - course controller
 */

class CourseController extends Opencast\Controller
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
        $this->course_id = Context::getId();
    }

    /**
     * This is the default action of this controller.
     */
    public function index_action()
    {
        Navigation::activateItem('/course/opencast');

        PageLayout::setTitle($this->_('Opencast Videos'));
        PageLayout::setBodyElementId('opencast-plugin');

        $this->studip_version = $this->getStudIPVersion();
        $this->languages = json_encode($GLOBALS['CONTENT_LANGUAGES']);

        $this->render_template('course/index', $GLOBALS['template_factory']->open('layouts/base.php'));
    }
}
