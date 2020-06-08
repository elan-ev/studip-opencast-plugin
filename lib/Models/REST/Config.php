<?php

namespace Opencast\Models\REST;

class Config {
    public static function getOCBaseVersion($config_id)
    {
        $oc = new RestClient($config_id);

        // for versions < 5
        $data = $oc->getJSON('/sysinfo/bundles/version?prefix=matterhorn');

        // for versions > 4 (name was changed to opencast after that)
        if (!$data){
            $data = $oc->getJSON('/sysinfo/bundles/version?prefix=opencast');
        }

        // always use the first found version information
        if (is_array($data->versions)) {
            $data = $data->versions[0];
        }

        return (int)substr($data->version, 0, 1);
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
