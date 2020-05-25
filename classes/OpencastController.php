<?php
/**
 * OpencastController - pimp the controller to work neatly in plugins
 *
 */

class OpencastController extends PluginController
{
    /**
     * Constructs the controller and provide translations methods.
     *
     * @param object $dispatcher
     * @see https://stackoverflow.com/a/12583603/982902 if you need to overwrite
     *      the constructor of the controller
     */
    public function __construct($dispatcher)
    {
        parent::__construct($dispatcher);

        $this->plugin = $dispatcher->current_plugin;

        // Localization
        $this->_ = function ($string) use ($dispatcher) {
            return call_user_func_array(
                [$dispatcher->current_plugin, '_'],
                func_get_args()
            );
        };

        $this->_n = function ($string0, $tring1, $n) use ($dispatcher) {
            return call_user_func_array(
                [$dispatcher->current_plugin, '_n'],
                func_get_args()
            );
        };
    }

    /**
     * Intercepts all non-resolvable method calls in order to correctly handle
     * calls to _ and _n.
     *
     * @param string $method
     * @param array $arguments
     * @return mixed
     * @throws RuntimeException when method is not found
     */
    public function __call($method, $arguments)
    {
        $variables = get_object_vars($this);
        if (isset($variables[$method]) && is_callable($variables[$method])) {
            return call_user_func_array($variables[$method], $arguments);
        }
        throw new RuntimeException("Method {$method} does not exist");
    }

    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        $this->plugin = $this->dispatcher->current_plugin;

        $this->flash = Trails_Flash::instance();
        $this->pluginpath = $this->dispatcher->trails_root;

        // notify on trails action
        $klass = substr(get_called_class(), 0, -10);
        $name  = sprintf('oc_admin.performed.%s_%s', $klass, $action);
        NotificationCenter::postNotification($name, $this);
    }

    /**
     * a wrapper to allow retrieving the plugin-url in the controllers
     *
     * @return string
     */
    public function getPluginURL()
    {
        return $GLOBALS['epplugin_path'];
    }
}
