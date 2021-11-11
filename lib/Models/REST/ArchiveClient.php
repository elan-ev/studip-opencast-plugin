<?php

namespace Opencast\Models\REST;

use Opencast\Models\Config;

class ArchiveClient extends RestClient
{
    public static $me;
    public        $serviceName = "Archive";

    function __construct($config_id)
    {
        parent::__construct($config_id, 'archive');
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
