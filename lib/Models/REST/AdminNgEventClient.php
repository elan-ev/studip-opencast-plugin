<?php
namespace Opencast\Models\REST;

use Opencast\Models\Config;

class AdminNgEventClient extends RestClient
{
    public static $me;
    public        $serviceName = "Admin-ngEvent";

    public function __construct($config_id = 1)
    {
        if ($config = Config::getConfigForService('admin-ngevent', $config_id)) {
            parent::__construct($config);
        } else {
            throw new \Exception ($this->serviceName . ': '
                . _('Die Opencast-Konfiguration wurde nicht korrekt angegeben'));
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
