<?php
/*
 * course.php - course controller
 */

use Opencast\Models\UploadStudygroup;
use Opencast\Models\Config;
use Opencast\LTI\OpencastLTI;

class CourseController extends OpencastController
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
        $this->config = Config::getConfigForCourse($this->course_id);
        $this->paella = $this->config['paella'] == '0' ? false : true;
    }

    /**
     * This is the default action of this controller.
     */
    public function index_action()
    {
        Navigation::activateItem('/course/opencast');
        Navigation::activateItem('course/opencast/episodes');

        $sidebar = Sidebar::Get();
        $actions = new TemplateWidget(
            _('Aktionen'),
            $this->get_template_factory()->open('course/action_widget')
        );
        $sidebar->addWidget($actions)->addLayoutCSSClass('action-widget');
    }

    public function scheduler_action()
    {
        Navigation::activateItem('/course/opencast');
        Navigation::activateItem('course/opencast/scheduler');

        $this->set_layout($GLOBALS['template_factory']->open('layouts/base.php'));

        $this->render_template('course/index');
    }

    public function manager_action()
    {
        Navigation::activateItem('/course/opencast');
        Navigation::activateItem('course/opencast/manager');
    }
}
