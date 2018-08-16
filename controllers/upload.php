<?php
/*
 * upload.php - upload controller
 */

class UploadController extends OpencastController
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

    public function upload_file_action()
    {
        if ($GLOBALS['perm']->have_studip_perm('tutor', Request::get('cid'))) {
            //Get a job object
            $job = OCJobManager::from_request();

            //Upload local/
            $job->upload_local_from_controller();

            //Upload to opencast
            $job->try_upload_to_opencast();

            //Remove old jobs if necessary
            OCJobManager::cleanup();
        }

        //go back
        $this->redirect('course/index');
    }
}
