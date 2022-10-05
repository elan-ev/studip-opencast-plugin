<?php

namespace Opencast\Models\REST;

use Opencast\Models\Config as ConfigModel;

class Config
{
    /**
     * Get the connected opencast instance version
     *
     * @param string $config_id the config id
     *
     * @return string|boolean the opencast version, or false if unable to get
     */
    public static function getOCBaseVersion($config_id)
    {
        $config = ConfigModel::getBaseServerConf($config_id);
        $oc = new RestClient($config);

        $response = $oc->opencastApi->sysinfo->getVersion('opencast');
        if ($response['code'] == 200) {
            if (isset($response['body']->version)) {
                return $response['body']->version;
            } else if (is_array($response['body']->versions)) {
                return array_reduce($response['body']->versions, function($carry, $item) {
                    if (empty($carry)) {
                        $carry = $item->version;
                    } else {
                        $carry .= ', ' . $item->version;
                    }
                    return $carry;
                });
            }
        }

        return false;
    }

    public static function retrieveRESTservices($components, $match_protocol)
    {
        $services = array();
        foreach ($components as $service) {
            if (!preg_match('/remote/', $service->type)
                && !preg_match('#https?://localhost.*#', $service->host)
                && mb_strpos($service->host, $match_protocol) === 0
            ) {
                $services[preg_replace(array("/\/docs/"), array(''), $service->host.$service->path)]
                         = preg_replace("/\//", '', $service->path);
            }
        }

        return $services;
    }
}
