<?php

namespace Opencast\Models;

class OCEndpoints extends \SimpleORMap
{
    protected static function configure($config = array())
    {
        $config['db_table'] = 'oc_endpoints';

        parent::configure($config);
    }

    /**
     *  function getEndpoints - get all Endpoints
     *
     *  @return array endpoints
     */
    static function getEndpoints()
    {

        return \SimpleCollection::createFromArray(
            self::findBySql('1 ORDER BY config_id, service_type')
        )->toArray();
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

                if (!$endpoint = self::find($service_url)) {
                    $endpoint = new self();
                }

                $endpoint->setData(compact('config_id', 'service_url','service_type'));
                return $endpoint->store();
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
        return self::deleteBySql(
            'config_id = ? AND service_type = ?',
            [$config_id, $service_type]
        );
    }
}
