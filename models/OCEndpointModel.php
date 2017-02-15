<?PHP
  
class OCEndpointModel
{
    /**
     *  function getEndpoints - get all Endpoints
     *
     *  @return array endpoints
     */
    static function getEndpoints(){
        $stmt = DBManager::get()->prepare("SELECT * FROM `oc_endpoints` WHERE 1");
        $stmt->execute();
        $endpoints =  $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $endpoints;
    }
    
    
    /**
     *  function getEndpoints - get all Endpoints
     *
     *  @return array endpoints
     */
    static function getBaseServerConf(){
        $stmt = DBManager::get()->prepare("SELECT * FROM `oc_config` WHERE 1");
        $stmt->execute();
        $config =  $stmt->fetch(PDO::FETCH_ASSOC);
        return $config;
    }


    /**
     *  function setEndpoint - sets config into DB for given REST-Service-Endpoint
     *
     * @param string $service_host
     * @param string $service_type
     * @param bool|string $service_url
     * @return
     * @throws Exception
     */
    function setEndpoint($service_host, $service_type, $service_url = false) {
        if(isset($service_host,$service_type)) {                    
            if($service_host != '') {
                $stmt = DBManager::get()->prepare("REPLACE INTO `oc_endpoints` (service_url,service_host, service_type) VALUES (?,?,?)");
                if($service_url === false) {
                    return $stmt->execute(array($service_host.'/'.$service_type, $service_host, $service_type));
                } else {
                    return $stmt->execute(array($service_url, $service_host, $service_type));
                }
            }
        } else {
            throw new Exception(_('Die Konfigurationsparameter wurden nicht korrekt angegeben.'));
        }

    }
}
?>