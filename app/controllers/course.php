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
        object_set_visit_module($this->plugin->getPluginId());
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

        $languages = [];
        foreach ($GLOBALS['CONTENT_LANGUAGES'] as $lang => $content) {
            $languages[str_replace('_', '-', $lang)] = $content;
        }

        $this->languages = json_encode($languages);

        // We need sidebar registration here in php side, in order for responsive navigation to work properly.
        $this->setSidebar();

        $this->render_template('course/index', $GLOBALS['template_factory']->open('layouts/base.php'));
    }

    /**
     * Adds the content to sidebar.
     * @info: The rendered sidebar of this function gets deleted from DOM, because we are using CourseSidebar.vue,
     * therefore, we only need this to happen in php level!
     */
    private function setSidebar()
    {
        $sidebar = Sidebar::get();

        $actions = new \TemplateWidget(
            $this->_('Aktionen'),
            $this->get_template_factory()->open('course/action_widget')
        );
        $sidebar->addWidget($actions);
    }
}
