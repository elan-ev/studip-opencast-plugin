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
    }
}
