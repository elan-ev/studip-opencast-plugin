<?php

namespace Opencast\Models\REST;

use Opencast\Models\Config;

class SchedulerClient extends RestClient
{
    public static $me;

    public function __construct($config_id = 1)
    {
        $this->serviceName = 'SchedulerClient';

        if ($config = Config::getConfigForService('recordings', $config_id)) {
            parent::__construct($config);
        } else {
            throw new \Exception ($this->serviceName . ': '
                . _('Die Opencast-Konfiguration wurde nicht korrekt angegeben'));
        }
    }

    /**
     * Deletes an event
     * 
     * @param string $event_id the event id
     * 
     * @return boolean success or not
     */
    public function deleteEvent($event_id)
    {
        $response = $this->opencastApi->recordings->deleteRecording($event_id);

        if ($response['code'] == 200) {
            return true;
        }
        return false;
    }

    /**
     * Updates an event
     * 
     * @param string $event_id the event id
     * @param int $start start of the event
     * @param int $end end of the event
     * @param string $agent agent name
     * @param string|array $users users
     * @param string $mediaPackage mediapackage
     * @param string|array $wfproperties workflow properties
     * @param string|array $agentparameters agent params
     * 
     * @return boolean success or not
     */
    public function updateEvent($event_id, $start = 0, $end = 0, $agent = '', $users = '', $mediaPackage = '', $wfproperties = '', $agentparameters = '')
    {
        $response = $this->opencastApi->recordings->updateRecording(
            $event_id,
            $start,
            $end,
            $agent,
            '',
            '',
            '',
            $agentparameters
        );

        if ($response['code'] == 200) {
            return true;
        }
        return false;
    }
}
