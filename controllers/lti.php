<?php

class LTIController extends OpencastController
{
    /**
     * Constructs the controller and provide translations methods.
     *
     * @param object $dispatcher
     *
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
     * @param array  $arguments
     *
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

    function index_action()
    {
        $this->render_text($this->_("Ups.."));
    }

    function generate_lti_launch_data_action()
    {
        // Get needed info
        $user_id = Request::get('user_id');
        $course_id = Request::get('course_id');
        $link_title = Request::get('link_title', 'OpenCastLink');
        $link_description = Request::get('link_description', 'OpenCastLinkDescription');
        $privacy = Request::get('privacy', false);
        $token = Request::get('token', '');

        // Tool info
        $tool_type = Request::get('tool_type', 'all');
        $episode_id = Request::get('episode_id');
        $series_id = Request::get('series_id');

        // Render mode
        $render_mode = Request::get('render_mode', 'json');

        // Do not send over network!
        $consumer_key = 'CONSUMERKEY';
        $consumer_secret = 'CONSUMERSECRET';

        // Generate ResourceLink and tool
        $resource_link = LTIResourceLink::generate_link($link_title, $link_description);
        $tool = OpencastLTI::generate_tool($tool_type);
        if ($tool_type == 'episode') {
            $tool = OpencastLTI::generate_tool('episode', $episode_id);
        }
        if ($tool_type == 'series') {
            $tool = OpencastLTI::generate_tool('series', $series_id);
        }

        // Build the unsigned LTI launch parameters
        $parameters_unsigned = OpencastLTI::generate_lti_launch_data($user_id, $course_id, $resource_link, $tool, $privacy);

        // Sign the launch parameters with OAuth
        $parameters_signed = OpencastLTI::sign_lti_data($parameters_unsigned, $consumer_key, $consumer_secret, $token);

        // Render as wanted
        if ($render_mode == 'json') {
            $this->render_text(json_encode($parameters_signed));
        }
        if ($render_mode == 'html') {
            $this->render_text($this->convert_to_hidden_input_fields($parameters_signed));
        }
        if ($render_mode == 'form') {
            $this->render_text($this->convert_to_formular($parameters_signed));
        }
    }

    private function convert_to_hidden_input_fields($parameters_signed)
    {
        $text = '';
        foreach ($parameters_signed as $key => $value) {
            $text .= '<input type="hidden" name="' . $key . '" value="' . $value . '">' . PHP_EOL;
        }

        return $text;
    }

    private function convert_to_formular($parameters_signed)
    {
        $input_fields = $this->convert_to_hidden_input_fields($parameters_signed);
        $form_id = uniqid('form');
        $form_start = '<form id="'.$form_id.'" action="https://oc-test.virtuos.uni-osnabrueck.de/lti" method="post" target="_blank">' . PHP_EOL;
        $form_input_fields = $input_fields;
        $form_submit_button = '<a href="javascript:void(0);" onclick="document.getElementById(\''.$form_id.'\').submit();">Click To Submit</a>'.PHP_EOL;
        $form_end = '</form>' . PHP_EOL;

        return $form_start . $form_input_fields . $form_submit_button . $form_end;
    }


}
