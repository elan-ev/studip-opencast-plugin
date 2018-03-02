<?php
/*
 * course.php - course controller
 * Copyright (c) 2010  André Klaßen
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once $this->trails_root . '/classes/OCRestClient/SearchClient.php';
require_once $this->trails_root . '/classes/OCRestClient/SeriesClient.php';
require_once $this->trails_root . '/classes/OCRestClient/IngestClient.php';
require_once $this->trails_root . '/classes/OCRestClient/UploadClient.php';
require_once $this->trails_root . '/classes/OCRestClient/ArchiveClient.php';
require_once $this->trails_root . '/models/OCModel.php';
require_once $this->trails_root . '/models/OCSeriesModel.php';
require_once $this->trails_root . '/models/OCCourseModel.class.php';
require_once $this->trails_root . '/models/OCEndpointModel.php';

require_once $this->trails_root . '/classes/OCJsonFile.php';

require_once $this->trails_root . '/classes/OCJobManager.php';
require_once $this->trails_root . '/classes/OCJob.php';
require_once $this->trails_root . '/classes/OCJobLocation.php';

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
        //Get a job object
        $job = OCJobManager::from_request();

        //Upload local
        $job->upload_local_from_controller();

        //Upload to opencast
        $job->try_upload_to_opencast();

        //Remove old jobs if necessary
        OCJobManager::cleanup();

        //go back
        $this->redirect('course/index');
    }
}
