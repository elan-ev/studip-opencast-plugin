<?php

use Opencast\Models\OCConfig;

class ArchiveClient extends OCRestClient
{
    static $me;
    public $serviceName = "Archive";

    function __construct($config_id)
    {
        if ($config = OCConfig::getConfigForService('archive', $config_id)) {
            parent::__construct($config);
        } else {
            throw new Exception (_("Die Konfiguration wurde nicht korrekt angegeben"));
        }
    }

    function deleteEvent($eventId) {
        $response = $this->getJSON("/".$eventId, array(), false, false, 'DELETE');
        return $response;
    }

    function applyWorkflow($workflowDefinitionId, $eventId) {
        $response = $this->getXML("/apply/".$workflowDefinitionId, array('mediaPackageIds' => $eventId), false, true);
        if ($response[1] == 204) {
            return true;
        } else {
            return false;
        }
    }

}
