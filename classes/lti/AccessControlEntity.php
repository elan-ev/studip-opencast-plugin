<?php

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
        $this->role   = $role;
        $this->action = $action;
        $this->allow  = $allow;
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

    public function toArray()
    {
        return [
            'allow'  => $this->allow,
            'role'   => $this->role,
            'action' => $this->action
        ];
    }

    public function get_role()
    {
        return $this->role;
    }

    public function get_action()
    {
        return $this->action;
    }

    public function get_allow()
    {
        return $this->allow;
    }
}
