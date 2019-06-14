<?php

class OCConfig
{
    /**
     * Return the complete configuration for the passed course
     *
     * @param  string $course_id
     *
     * @return mixed  the configuration data for the passed course
     */
    static function getConfigForCourse($course_id)
    {
        static $config;

        if (!$config[$course_id]) {
            $config_id = self::getConfigIdForCourse($course_id);
            if ($config_id) {
                $settings  = Configuration::instance($config_id);
                $oc_config = OCEndpointModel::getBaseServerConf($config_id);

                $config[$course_id] = array_merge($oc_config, $settings->toArray());
            }
        }

        return $config[$course_id];
    }

    /**
     * function getConfig  - retries configutation for a given REST-Service-Client
     *
     * @param string $service_type - client label
     *
     * @return array configuration for corresponding client
     *
     */
    static function getConfigForService($service_type, $config_id = 1)
    {
        if (isset($service_type)) {
            $stmt = DBManager::get()->prepare("SELECT * FROM `oc_endpoints`
                WHERE service_type = ? AND config_id = ?");
            $stmt->execute([$service_type, $config_id]);
            $config = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($config) {
                $stmt = DBManager::get()->prepare("SELECT * FROM `oc_config`
                    WHERE config_id = ?");
                $stmt->execute([$config_id]);
                $config = $config + $stmt->fetch(PDO::FETCH_ASSOC);

                return $config;
            } else {
                return [
                    self::empty_config()
                ];
                #throw new Exception(sprintf(_("Es sind keine Konfigurationsdaten für den Servicetyp **%s** vorhanden."), $service_type));
            }

        } else {
            throw new Exception(_("Es wurde kein Servicetyp angegeben."));
        }
    }

    /**
     *  function setConfig - sets config into DB for given REST-Service-Client
     *
     * @param string $service_url
     * @param string $service_user
     * @param string $service_password
     *
     * @return
     * @throws Exception
     */
    static function setConfig($config_id = 1, $service_url, $service_user, $service_password, $version)
    {
        if (isset($service_url, $service_user, $service_password, $version)) {

            $stmt = DBManager::get()->prepare('REPLACE INTO `oc_config`
                (config_id, service_url, service_user, service_password, service_version)
                VALUES (?, ?, ?, ?, ?)'
            );

            return $stmt->execute([
                $config_id, $service_url, $service_user,
                $service_password, (int)$version
            ]);
        } else {
            throw new Exception(_('Die Konfigurationsparameter wurden nicht korrekt angegeben.'));
        }

    }

    static function clearConfigAndAssociatedEndpoints($config_id)
    {
        $stmt = DBManager::get()->prepare("DELETE FROM `oc_config` WHERE config_id = ?;");
        $stmt->execute([$config_id]);
        $stmt = DBManager::get()->prepare("DELETE FROM `oc_endpoints` WHERE config_id = ?;");

        return $stmt->execute([$config_id]);
    }

    /**
     * get id of used config for passed course
     *
     * @param string $course_id
     *
     * @return int
     */
    static function getConfigIdForCourse($course_id)
    {
        $stmt = DBManager::get()->prepare("SELECT config_id
            FROM oc_seminar_series
            WHERE seminar_id = ?");

        $stmt->execute([$course_id]);

        return $stmt->fetchColumn();
    }

    /**
     * get course-id for passed series
     *
     * @param string $series_id
     *
     * @return string
     */

    static function getCourseIdForSeries($series_id)
    {
        $stmt = DBManager::get()->prepare("SELECT seminar_id
            FROM oc_seminar_series
            WHERE series_id = ?");

        $stmt->execute([$series_id]);

        return $stmt->fetchColumn();
    }

    /**
     * get config-id for passed series
     *
     * @param string $series_id
     *
     * @return string
     */

    static function getConfigIdForSeries($series_id)
    {
        $stmt = DBManager::get()->prepare("SELECT config_id
            FROM oc_seminar_series
            WHERE series_id = ?");

        $stmt->execute([$series_id]);

        return $stmt->fetchColumn() ?: 1;
    }


    /**
     * get course-id for passed series
     *
     * @param string $series_id
     *
     * @return string
     */

    static function getConfigIdForWorkflow($workflow_id)
    {
        $stmt = DBManager::get()->prepare("SELECT config_id
            FROM oc_seminar_workflows
            WHERE workflow_id = ?");

        $stmt->execute([$workflow_id]);

        return $stmt->fetchColumn();
    }

    public static function empty_config()
    {
        return [
            'config_id'        => 'error',
            'service_url'      => 'error',
            'service_user'     => 'error',
            'service_password' => 'error',
            'service_version'  => 'error'
        ];
    }
}
