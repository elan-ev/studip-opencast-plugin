<?php
/**
 * This class is used to create the job locations and file structures
 *
 */

class OCJobLocation
{
    private $job_id;

    public function __construct($job_id)
    {
        $this->job_id = $job_id;
    }

    /**
     * @return string the path to this job's directory
     */
    public function path()
    {
        return OCJobManager::job_path($this->job_id);
    }

    /**
     * @return bool true if this job has a directory structure underlying
     */
    public function exist()
    {
        return file_exists($this->path());
    }

    /**
     * Used to create the job file structure
     */
    public function create()
    {
        mkdir($this->path(), 0750, true);
    }
}
