<?php
require_once "OCRestClient.php";


class MediaPackageClient extends OCRestClient {
    static $me;
    public function __construct($config_id = 1)
    {
        $this->serviceName = 'MediaPackageClient';
        try {
            if ($config = parent::getConfig('mediapackage', $config_id)) {
                parent::__construct($config['service_url'],
                                    $config['service_user'],
                                    $config['service_password']);
            } else {
                throw new Exception (_("Die Konfiguration wurde nicht korrekt angegeben"));
            }
        } catch(Exception $e) {

        }
    }
    public function addTrack($mediaPackage, $trackURI, $flavor)
    {
        $data = array('mediapackage' => $mediaPackage,
            'trackUri' => $trackURI,
            'flavor' => $flavor);
        if($res = $this->getXML('/addTrack', $data, false)) {
            return $res;
        } else return false;
    }
    
  
}
