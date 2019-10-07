<?php
/**
 * @author          Jan-Frederik Leissner <jleissner@uos.de>
 * @copyright   (c) Authors
 * @version         1.0 (12:36)
 */

class AccessControlList
{
    const ACTION_READ = 'read';
    const ACTION_WRITE = 'write';
    const ALLOW_YES = true;
    const ALLOW_NO = false;

    private $entities;
    private $name;

    public function __construct($name)
    {
        $this->name = $name;
        $this->entities = [];
    }

    public function add_ace(AccessControlEntity $to_add)
    {
        foreach ($this->entities as $entity) {
            if ($entity->equals($to_add)) {
                return true;
            }
        }

        // skip entities with allowed = false
        if ($to_add->get_allow()) {
            $this->entities[] = $to_add;
        }
    }

    public function add_acl(AccessControlList $to_add)
    {
        foreach ($to_add->get_entities() as $entity) {
            $this->add_ace($entity);
        }
    }

    public function as_xml()
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<acl xmlns="http://org.opencastproject.security">'
            . implode('', $this->entities) . '</acl>';
    }

    public function get_entities()
    {
        return $this->entities;
    }

    public function get_name()
    {
        return $this->name;
    }

}

class AccessControlEntity
{
    private $role;
    private $action;
    private $allow;

    /**
     * AccessControlEntity constructor.
     *
     * @param $role
     * @param $action
     * @param $allow
     */
    public function __construct($role, $action, $allow)
    {
        $this->role = $role;
        $this->action = $action;
        $this->allow = $allow;
    }

    public function equals(AccessControlEntity $other)
    {
        return $this->as_xml() == $other->as_xml();
    }

    private function allow()
    {
        return ($this->allow ? 'true' : 'false');
    }

    public function as_xml()
    {
        if ($this->allow) {
            return '<ace><role>' . $this->role . '</role><action>' . $this->action . '</action><allow>' . $this->allow() . '</allow></ace>';
        }

        return '';
    }

    public function __toString()
    {
        return $this->as_xml();
    }

    public function get_role()
    {
        return $this->role;
    }

    public function get_action(){
        return $this->action;
    }

    public function get_allow(){
        return $this->allow;
    }
}
