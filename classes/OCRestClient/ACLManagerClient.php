<?php
/**
 * @author          Jan-Frederik Leissner <jleissner@uos.de>
 * @copyright   (c) Authors
 * @version         1.0 (12:34)
 */

class ACLManagerClient extends OCRestClient
{
    static $me;
    public $serviceName = "ACL-Manager";

    function __construct()
    {
        if ($config = parent::getConfig('acl-manager')) {
            parent::__construct($config);
        } else {
            throw new Exception (_("Die Konfiguration wurde nicht korrekt angegeben"));
        }
    }

    function createACL(AccessControlList $acl)
    {
        $data = [
            'name' => $acl->get_name(),
            'acl'  => $acl->as_xml()
        ];
        return $this->getJSON('/acl', $data, false, true);
    }

    
}