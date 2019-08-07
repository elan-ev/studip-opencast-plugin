<?php
/**
 * OpencastController - pimp the controller to work neatly in plugins
 *
 */

class OpencastController extends StudipController
{
    function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        $this->plugin = $this->dispatcher->current_plugin;

        $this->flash = Trails_Flash::instance();
    }

    /**
     * a wrapper to allow retrieving the plugin-url in the controllers
     *
     * @return string
     */
    function getPluginURL()
    {
        return $GLOBALS['epplugin_path'];
    }

    /**
     * overwrite the default url_for to enable to it work in plugins
     *
     * @param type $to
     * @return type
     */
    function url_for($to = '')
    {
        $args = func_get_args();

        // find params
        $params = array();
        if (is_array(end($args))) {
            $params = array_pop($args);
        }

        // urlencode all but the first argument
        $args = array_map('urlencode', $args);
        $args[0] = $to;

        return PluginEngine::getURL($this->dispatcher->current_plugin, $params, join('/', $args));
    }

    /**
     * Throw an array at this function and it will call render_text to output
     * the json-version of that array while setting an appropriate http-header
     *
     * @param array $data
     */
    function render_json($data)
    {
        $this->response->add_header('Content-Type', 'application/json');
        $this->render_text(json_encode($data));
    }
}
