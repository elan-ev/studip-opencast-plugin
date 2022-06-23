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

        // for versions < 5
        $response = $oc->opencastApi->sysinfo->getVersion('matterhorn');
        if ($response['code'] == 200 && isset($response['body']->version)) {
            return $response['body']->version;
        }

        // for versions > 4 (name was changed to opencast after that)
        $response = $oc->opencastApi->sysinfo->getVersion('opencast');
        if ($response['code'] == 200 && isset($response['body']->version)) {
            return $response['body']->version;
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
