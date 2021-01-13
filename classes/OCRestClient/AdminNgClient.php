<?php

use Opencast\Models\OCConfig;

class AdminNgClient extends OCRestClient
{
    public static $me;
    public        $serviceName = "Admin-Ng";
    
    public function __construct($config_id = 1)
    {
        if ($config = OCConfig::getConfigForService('admin-ng', $config_id)) {
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
        trigger_error($result[1], E_USER_ERROR);
        
        if (in_array($result[1], [200, 202])) {
            return true;
        }
        return false;
    }
}