<?php

class AdminController extends Opencast\Controller
{
    public function __construct($dispatcher)
    {
        parent::__construct($dispatcher);
    }

    function before_filter(&$action, &$args)
    {
        if (!$GLOBALS['perm']->have_perm('root')) {
            throw new AccessDeniedException();
        }

        // notify on trails action
        $class = substr(get_called_class(), 0, -10);
        $name = sprintf('oc_admin.performed.%s_%s', $class, $action);
        NotificationCenter::postNotification($name, $this);

        parent::before_filter($action, $args);
    }

    public function index_action()
    {
        Navigation::activateItem('/admin/config/oc-config');
        PageLayout::setBodyElementId('opencast-plugin');
    }
}
