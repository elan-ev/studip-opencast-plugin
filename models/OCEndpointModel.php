<?PHP

class OCEndpointModel
{
    /**
     *  function getEndpoints - get all Endpoints
     *
     *  @return array endpoints
     */
    static function getEndpoints()
    {
        $stmt = DBManager::get()->prepare("SELECT * FROM `oc_endpoints`
            WHERE 1
            ORDER BY config_id, service_type");
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     *  function getEndpoints - get all Endpoints
     *
     *  @return array endpoints
     */
    static function getBaseServerConf($config_id = null)
    {
        if (is_null($config_id)) {
            return DBManager::get()->query('SELECT * FROM oc_config
                ORDER BY config_id ASC')->fetchAll(\PDO::FETCH_UNIQUE|\PDO::FETCH_ASSOC);
        }

        $stmt = DBManager::get()->prepare("SELECT * FROM `oc_config` WHERE config_id = ?");
        $stmt->execute(array($config_id));

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


    /**
     *  function setEndpoint - sets config into DB for given REST-Service-Endpoint
     *
     *  @param string $service_url
     *  @param string $service_type
     */
    static function setEndpoint($config_id, $service_url, $service_type)
    {
        if (isset($config_id, $service_url, $service_type)) {
            if ($service_url != '') {
                $stmt = DBManager::get()->prepare("REPLACE INTO `oc_endpoints`
                    (config_id, service_url, service_type)
                    VALUES (?,?,?)"
                );

                return $stmt->execute(array(
                    $config_id,
                    $service_url,
                    $service_type
                ));
            }
        } else {
            throw new Exception(_('Die Konfigurationsparameter wurden nicht korrekt angegeben.'));
        }
    }

    /**
     * Remove passed endpoint from config
     *
     * @param  int $config_id
     * @param  string $service_type
     * @return mixed  result of the executed db-stmt
     */
    static function removeEndpoint($config_id, $service_type)
    {
        $stmt = DBManager::get()->prepare('DELETE FROM oc_endpoints
            WHERE config_id = ? AND service_type = ?');
        return $stmt->execute([$config_id, $service_type]);
    }
}
