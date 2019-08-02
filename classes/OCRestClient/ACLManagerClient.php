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

        if ($acl = $this->getACLByName($acl->get_name())) {
            $this->removeACL($acl->id);

        }

        return $this->postJSON('/acl', $data);
    }

    function removeACL($acl_id)
    {
        $result = $this->deleteJSON('/acl/' . $acl_id, true);

        return $result[1] == 200 || $result[1] == 204;
    }

    function applyACLto($type, $id, $acl_id = null)
    {
        $data = [];

        if ($acl_id) {
            $data = [
                'aclId' => $acl_id
            ];
        }

        $result = $this->postJSON('/apply/' . $type . '/' . $id, $data);

        return $result[1] == 200;
    }

    function getAllACLs()
    {
        static $acls;

        if (!isset($acls)) {
            $acls = $this->getJSON('/acl/acls.json');
        }

        return $acls;
    }

    function getACLByName($name)
    {
        foreach ($this->getAllACLs() as $acl) {
            if ($acl->name == $name) {
                return $acl;
            }
        }

        return false;
    }

    function getACL($acl_id)
    {
        return $this->getJSON('/acl/' . $acl_id);
    }

}
