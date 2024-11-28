<?php
namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Api extends \Codeception\Module
{
    protected $requiredFields = [
        'user',
        'password',
        'author_name',
        'author_password',
        'course_student',
        'config_id',
        'course_id',
        'opencast_rest_url',
        'api_token',
    ];

    public function getConfig(): array {
        return $this->config;
    }
}
