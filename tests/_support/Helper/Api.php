<?php
namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Api extends \Codeception\Module
{
    protected $requiredFields = ['user', 'password', 'config_id', 'course_id'];

    public function getConfig(): array {
        return $this->config;
    }
}
