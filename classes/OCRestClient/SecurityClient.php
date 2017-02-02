<?php

/**
 * Created by PhpStorm.
 * User: jayjay
 * Date: 02.02.17
 * Time: 10:34
 */
require_once "OCRestClient.php";
class SecurityClient extends OCRestClient
{
    static $me;
    public $serviceName = "Security";
    function __construct()
    {
        try {
            if ($config = parent::getConfig('apisecurity')) {
                parent::__construct($config['service_url'],
                    $config['service_user'],
                    $config['service_password']);
            } else {
                throw new Exception (_("Die Konfiguration wurde nicht korrekt angegeben"));
            }
        } catch(Exception $e) {

        }
    }

    function signURL($url) {
        $res = $this->getJSON('/sign', array('url' => $url), false, true);
        var_dump($res);
    }
}