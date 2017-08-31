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
    static function getBaseServerConf($config_id){
        $stmt = DBManager::get()->prepare("SELECT * FROM `oc_config` WHERE config_id = ?");
        $stmt->execute(array($config_id));
        $config =  $stmt->fetch(PDO::FETCH_ASSOC);
        return $config;
    }


    /**
     *  function setEndpoint - sets config into DB for given REST-Service-Endpoint
     *
     *  @param string $service_host
     *  @param string $service_type
     */
    function setEndpoint($config_id, $service_host, $service_type) {
        if(isset($config_id, $service_host,$service_type)) {
            if($service_host != '') {

                $stmt = DBManager::get()->prepare("REPLACE INTO `oc_endpoints` (config_id, service_url,service_host, service_type) VALUES (?,?,?,?)");
                return $stmt->execute(array($config_id, $service_host.'/'.$service_type, $service_host, $service_type));
            }
        } else {
            throw new Exception(_('Die Konfigurationsparameter wurden nicht korrekt angegeben.'));
        }

    }
}
?>
