<?php
namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Api extends \Codeception\Module
{
    protected $requiredFields = [
        'opencast_rest_url',
        'config_id',
        'api_token',
        'opencast_admin_user',
        'opencast_admin_password',
        'dozent_name',
        'dozent_password',
        'course_student',
        'author_name',
        'author_password',
        'course_id',
    ];

    public function getConfig(): array {
        return $this->config;
    }
}
