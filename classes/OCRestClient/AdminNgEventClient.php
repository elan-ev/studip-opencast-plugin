<?php

use Opencast\Models\OCConfig;

class AdminNgEventClient extends OCRestClient
{
    public static $me;
    public        $serviceName = "Admin-ngEvent";
    
    public function __construct($config_id = 1)
    {
        if ($config = OCConfig::getConfigForService('admin-ngevent', $config_id)) {
            parent::__construct($config);
        } else {
            throw new Exception (_('Die Konfiguration wurde nicht korrekt angegeben'));
        }
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
