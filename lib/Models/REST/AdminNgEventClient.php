<?php

namespace Opencast\Models\REST;

use Opencast\Models\Config;

class AdminNgEventClient extends RestClient
{
    public static $me;
    public        $serviceName = "Admin-ngEvent";

    public function __construct($config_id = 1)
    {
        parent::__construct($config_id, 'admin-ngevent');
    }

    /**
     * delelteEpisode -  retracts and deletes an episode
     *
     * @param string $episode_id - episode identifier
     *
     * @return bool success or not
     */
    public function deleteEpisode($episode_id)
    {
        $result = $this->deleteJSON('/' . $episode_id, true);

        if (in_array($result[1], [200, 202])) {
            return true;
        }
        return false;
    }
}
