<?php
/**
 * @author          Jan-Frederik Leissner <jleissner@uos.de>
 * @copyright   (c) Authors
 * @version         1.0 (10:08)
 */

class OCJobLocation
{
    private $job_id;

    public function __construct($job_id)
    {
        $this->job_id = $job_id;
    }

    public function path()
    {
        return OCJobManager::$BASE_PATH . '/' . $this->job_id;
    }

    public function exist()
    {
        return file_exists($this->path());
    }

    public function create()
    {
        $old_mask = umask(0);
        mkdir($this->path(), 0777, TRUE);
        umask($old_mask);
    }
}