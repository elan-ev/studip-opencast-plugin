<?php
/**
 * @author          Jan-Frederik Leissner <jleissner@uos.de>
 * @copyright   (c) Authors
 * @version         1.0 (12:34)
 */

use Opencast\Models\OCConfig;

class ACLManagerClient extends OCRestClient
{
    public static $me;
    public        $serviceName = "ACL-Manager";

    public function __construct($config_id)
    {
        if ($config = OCConfig::getConfigForService('acl-manager', $config_id)) {
            parent::__construct($config);
        } else {
            throw new Exception (_('Die Konfiguration wurde nicht korrekt angegeben'));
        }
    }

    public function createACL(AccessControlList $acl)
    {
        $data = [
            'name' => $acl->get_name(),
            'acl'  => $acl->as_xml()
        ];

        if ($oc_acl = $this->getACLByName($acl->get_name())) {
            // check, if acls differ and only then remove the acl and set a new one
            if (count($oc_acl->acl->ace) != count($acl->get_entities())) {
                $this->removeACL($oc_acl->id);
            } else {
                return $oc_acl;
            }
        }
        list($result, $code) = $this->postJSON('/acl', $data, true);

        if ((int)$code === 200) {
            return $result;
        } else if ((int)$code === 409) {
            return null;
        }

        throw new Exception(_('Es ist ein Fehler beim Anlegen der ACL aufgetreten.'));
    }

    public function removeACL($acl_id)
    {
        $result = $this->deleteJSON('/acl/' . $acl_id, true);

        return (int)$result[1] === 200 || (int)$result[1] === 204;
    }

    public function applyACLto($type, $id, $acl_id = null)
    {
        $data = [];

        $data = [
            'aclId' => $acl_id
        ];

        $result = $this->postJSON('/apply/' . $type . '/' . $id, $data, true);

        return $result[1] == 200;
    }

    public function getAllACLs()
    {
        static $acls;

        if (!isset($acls)) {
            $acls = $this->getJSON('/acl/acls.json');
        }

        return $acls ?: [];
    }

    public function getACLByName($name)
    {
        foreach ($this->getAllACLs() as $acl) {
            if ($acl->name == $name) {
                return $acl;
            }
        }

        return false;
    }

    public function getACL($acl_id)
    {
        return $this->getJSON('/acl/' . $acl_id);
    }
}
