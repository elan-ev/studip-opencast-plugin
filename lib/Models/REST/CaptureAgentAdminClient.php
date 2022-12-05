<?php
namespace Opencast\Models\REST;

use Opencast\Models\Config;

class CaptureAgentAdminClient extends RestClient
{
    public static $me;

    public function __construct($config_id = 1)
    {
        $this->serviceName = 'CaptureAgentAdminClient';

        if ($config = Config::getConfigForService('capture-admin', $config_id)) {
            parent::__construct($config);
        } else {
            throw new \Exception ($this->serviceName . ': '
                . _('Die Opencast-Konfiguration wurde nicht korrekt angegeben'));
        }
    }

    /**
     * Retrieves capture agents of connected opencast
     * 
     * @return array|boolean array of capture agent list or false if unable to get.
     */
    public function getCaptureAgents()
    {
        $response = $this->opencastApi->captureAdmin->getAgents();
        
        if ($response['code'] == 200) {
            return $this->sanitizeAgents($response['body']);
        }
        return false;
    }

    /**
     * Retrieves the capabilities of a given capture agent
     * 
     * @param string $agent_name name of capture agent
     * 
     * @return object|boolean capability object, or false if unable to get
     */
    public function getCaptureAgentCapabilities($agent_name)
    {
        $response = $this->opencastApi->captureAdmin->getAgentCapabilities($agent_name);
        
        if ($response['code'] == 200) {
            $capability = $response['body'];
            $x = 'properties-response';
            $item = isset($capability->$x->properties->item) ? $capability->$x->properties->item : false;
            return $item;
        }
        return false;
    }

    /**
     * Sanitizes the list of capture agents.
     * 
     * @param object $agents the list of agents
     * 
     * @return array agents array list
     */
    private function sanitizeAgents($agents)
    {
        if (!isset($agents->agents->agent) || empty($agents->agents->agent)) {
            return [];
        }
        
        if (is_array($agents->agents->agent)) {
            return $agents->agents->agent;
        }

        return [$agents->agents->agent];
    }
}
