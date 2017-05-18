<?php

/**
 * Created by PhpStorm.
 * User: jayjay
 * Date: 22.02.17
 * Time: 09:50
 */

require_once "OCRestClient.php";

class ArchiveClient extends OCRestClient
{
    static $me;
    public $serviceName = "Archive";
    function __construct()
    {
        try {
            if ($config = parent::getConfig('archive')) {
                parent::__construct($config['service_url'],
                    $config['service_user'],
                    $config['service_password']);
            } else {
                throw new Exception (_("Die Konfiguration wurde nicht korrekt angegeben"));
            }
        } catch(Exception $e) {

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