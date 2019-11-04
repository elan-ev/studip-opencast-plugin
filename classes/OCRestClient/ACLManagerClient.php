<?php
/**
 * @author          Jan-Frederik Leissner <jleissner@uos.de>
 * @copyright   (c) Authors
 * @version         1.0 (12:34)
 */

use Opencast\Models\OCConfig;

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

        if ($oc_acl = $this->getACLByName($acl->get_name())) {
            // check, if acls differ and only then remove the acl and set a new one
            if (sizeof($oc_acl->acl->ace) != sizeof($acl->get_entities())) {
                $this->removeACL($oc_acl->id);
            } else {
                return $oc_acl;
            }
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

        $data = [
            'aclId' => $acl_id
        ];

        $result = $this->postJSON('/apply/' . $type . '/' . $id, $data, true);

        return $result[1] == 200;
    }

    function getAllACLs()
    {
        static $acls;

        if (!isset($acls)) {
            $acls = $this->getJSON('/acl/acls.json');
        }

        return $acls ?: [];
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
