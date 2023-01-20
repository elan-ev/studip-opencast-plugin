<?php

use Opencast\Models\Workflow;

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

        $plugin_id = PluginManager::getInstance()->getPluginInfo('OpenCast')['id'];
        $plugin_roles = RolePersistence::getAssignedPluginRoles($plugin_id);
        $has_role = false;
        foreach ($plugin_roles as $plugin_role) {
            if ($plugin_role->rolename === 'Nobody') {
                $has_role = true;
            }
        }
        if (!$has_role) {
            PageLayout::postMessage(MessageBox::warning(_('Das Plugin benötigt die "Nobody"-Rolle, um Opencast den Abruf der Nutzendenberechtigungen zu ermöglichen. Diese Rolle wurde jedoch noch nicht zugewiesen, deshalb ist das Plugin momentan nur eingeschränkt funktionsfähig.')));
        }

        Workflow::updateWorkflows();
    }
}
