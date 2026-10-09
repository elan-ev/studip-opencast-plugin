<?php
/*
 * course.php - course controller
 */

use Opencast\Models\Tos;
use Opencast\Providers\Perm;

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

        $this->checkTermsOfService($action);
    }

    /**
     * Shows the terms of service, which have to be accepted before the plugin
     * can be used in this course.
     */
    public function tos_action()
    {
        if (!Tos::isRequired() || !Perm::editAllowed($this->course_id)) {
            return $this->redirect('course/index');
        }

        Navigation::activateItem('/course/opencast');
        PageLayout::setTitle($this->_('Opencast - Nutzungsvereinbarung'));

        // The admin config stores the WYSIWYG content without Stud.IP's HTML
        // marker (see Markup::markAsHtml()), so mark it here. formatReady()
        // purifies the HTML.
        $tos_text = trim(Tos::getText($GLOBALS['user']->id));
        if (substr($tos_text, 0, 1) === '<' && !preg_match('/^<!--\s*HTML/i', $tos_text)) {
            $tos_text = '<!--HTML-->' . $tos_text;
        }
        $this->tos_text = $tos_text;

        $this->set_layout($GLOBALS['template_factory']->open('layouts/base'));
    }

    /**
     * Stores the acceptance of the terms of service for the current user.
     */
    public function accept_tos_action()
    {
        CSRFProtection::verifyUnsafeRequest();

        if (Tos::isRequired() && Perm::editAllowed($this->course_id)) {
            Tos::accept($GLOBALS['user']->id);
        }

        $this->redirect('course/index');
    }

    /**
     * Shown to course members without edit permissions, as long as no lecturer
     * of the course has accepted the terms of service.
     */
    public function access_denied_action()
    {
        if (!Tos::isRequired()) {
            return $this->redirect('course/index');
        }

        Navigation::activateItem('/course/opencast');
        PageLayout::setTitle($this->_('Opencast - Zugriff verweigert'));

        $this->set_layout($GLOBALS['template_factory']->open('layouts/base'));
    }

    /**
     * Redirects to the terms of service or the access denied page, if the
     * terms of service have to be accepted and have not been accepted yet.
     *
     * @param string $action the requested action
     */
    private function checkTermsOfService($action)
    {
        if (!Tos::isRequired()
            || in_array($action, ['tos', 'accept_tos', 'access_denied'])
            || $GLOBALS['perm']->have_perm('root')
        ) {
            return;
        }

        if (Perm::editAllowed($this->course_id)) {
            if (!Tos::hasAccepted($GLOBALS['user']->id)) {
                $this->redirect('course/tos');
            }
        } elseif (!Tos::isAcceptedForCourse($this->course_id)) {
            $this->redirect('course/access_denied');
        }
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
