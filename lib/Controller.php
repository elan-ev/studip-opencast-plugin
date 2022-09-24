<?php

namespace Opencast;

use PluginController;
use PageLayout;
use Trails_Flash;
use Config;
use PluginEngine;
use Request;
use URLHelper;


class Controller extends PluginController
{
    protected $allow_nobody = false; //nobody is not allowed and always gets a login-screen

    public function before_filter(&$action, &$args)
    {
        global $user;

        if ($user->id == 'nobody') {
            $this->redirect(URLHelper::getURL('index.php'));
        }
        parent::before_filter($action, $args);

        $this->plugin = $this->dispatcher->current_plugin;
        $this->flash  = Trails_Flash::instance();

        $dispatcher = $this->dispatcher;
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
     * overwrite the default url_for to enable to it work in plugins
     * @param type $to
     * @return type
     */
    public function url_for($to = '')
    {
        $args = func_get_args();

        // find params
        $params = [];
        if (is_array(end($args))) {
            $params = array_pop($args);
        }

        // urlencode all but the first argument
        $args    = array_map('urlencode', $args);
        $args[0] = $to;

        return PluginEngine::getURL($this->plugin, $params, join('/', $args));
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

    /**
     * Throw an array at this function and it will call render_text to output
     * the json-version of that array while setting an appropriate http-header
     * @param array $data
     */
    public function render_json($data)
    {
        $this->response->add_header('Content-Type', 'application/json');
        $this->render_text(json_encode($data));
    }

    public function getStudIPVersion()
    {
        $studip_version = \StudipVersion::getStudipVersion();
        if (empty($studip_version)) {
            $manifest = OpenCast::getPluginManifestInfo();
            $studip_version = isset($manifest["studipMinVersion"]) ? $manifest["studipMinVersion"] : 4.6;
        }

        return floatval($studip_version);
    }
}
