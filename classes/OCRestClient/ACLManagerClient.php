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

    function __construct($config_id)
    {
        if ($config = OCConfig::getConfigForService('acl-manager', $config_id)) {
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

        return $this->getJSON('/acl', $data, false);
    }

    function removeACL($acl_id){
        curl_setopt($this->ochandler, CURLOPT_CUSTOMREQUEST, "DELETE");
        $result = $this->getJSON('/acl/'.$acl_id, [], true, true);
        curl_setopt($this->ochandler, CURLOPT_CUSTOMREQUEST, null);

        return $result[1] == 200 || $result[1] == 204;
    }

    function applyACLto($type, $id, $acl_id)
    {
        $data = [
            'aclId' => $acl_id
        ];

        $result = $this->getJSON('/apply/' . $type . '/' . $id, $data, false, true);

        return $result[1] == 200;
    }

    function getAllACLs()
    {
        return $this->getJSON('/acl/acls.json');
    }

    function getACL($acl_id)
    {
        return $this->getJSON('/acl/' . $acl_id);
    }

}
