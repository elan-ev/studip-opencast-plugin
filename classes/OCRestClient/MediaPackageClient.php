<?php
require_once "OCRestClient.php";


class MediaPackageClient extends OCRestClient {
    static $me;
    public function __construct() 
    {
        $this->serviceName = 'MediaPackageClient';
        if ($config = parent::getConfig('mediapackage')) {
            parent::__construct($config['service_url'],
                                $config['service_user'],
                                $config['service_password']);
        } else {
            throw new Exception (_("Die Mediapackageservice Konfiguration wurde nicht im gültigen Format angegeben."));
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