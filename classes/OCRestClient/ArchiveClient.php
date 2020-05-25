<?php

use Opencast\Models\OCConfig;

class ArchiveClient extends OCRestClient
{
    public static $me;
    public        $serviceName = "Archive";

    function __construct($config_id)
    {
        if ($config = OCConfig::getConfigForService('archive', $config_id)) {
            parent::__construct($config);
        } else {
            throw new Exception (_('Die Konfiguration wurde nicht korrekt angegeben'));
        }
    }

    public function deleteEvent($eventId)
    {
        $response = $this->getJSON("/{$eventId}", [], false, false, 'DELETE');
        return $response;
    }

    public function applyWorkflow($workflowDefinitionId, $eventId)
    {
        $response = $this->getXML("/apply/{$workflowDefinitionId}", ['mediaPackageIds' => $eventId], false, true);
        if ($response[1] == 204) {
            return true;
        } else {
            return false;
        }
    }
}
