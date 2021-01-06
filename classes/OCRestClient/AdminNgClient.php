<?php

use Opencast\Models\OCConfig;

class AdminNgClient extends OCRestClient
{
    public static $me;
    
    public function __construct($config_id = 1)
    {
        $this->serviceName = 'AdminNgClient';

        if ($config = OCConfig::getConfigForService('admin-ng', $config_id)) {
            parent::__construct($config);
        } else {
            throw new Exception (_('Die Konfiguration wurde nicht korrekt angegeben'));
        }
    }
    
    # in SchedulerClient -> deleteEventForSeminar
}
