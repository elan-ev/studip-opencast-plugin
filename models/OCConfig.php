<?php

namespace Opencast\Models;

use Opencast\Models\OCSeminarSeries;

class OCConfig extends \SimpleORMap
{
    protected static function configure($config = array())
    {
        $config['db_table'] = 'oc_config';

        parent::configure($config);
    }

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
                $settings  = \Configuration::instance($config_id);
                $oc_config = self::getBaseServerConf($config_id);

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
            $config = OCEndpoints::findOneBySQL(
                'service_type = ? AND config_id = ?' ,
                [$service_type, $config_id]
            )->toArray();

            if ($config) {
                return $config + self::find($config_id)->toArray();
            } else {
                return [
                    self::empty_config()
                ];
            }

        } else {
            throw new \Exception(_("Es wurde kein Servicetyp angegeben."));
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
            if (!$config = self::find($config_id)) {
                $config = new self();
            }

            $version = (int)$version;

            $config->setData(compact('config_id', 'service_url',
                'service_user', 'service_password', 'version'));
            return $config->store();
        } else {
            throw new \Exception(_('Die Konfigurationsparameter wurden nicht korrekt angegeben.'));
        }
    }

    static function clearConfigAndAssociatedEndpoints($config_id)
    {
        $stmt = \DBManager::get()->prepare("DELETE FROM `oc_config` WHERE config_id = ?;");
        $stmt->execute([$config_id]);
        $stmt = \DBManager::get()->prepare("DELETE FROM `oc_endpoints` WHERE config_id = ?;");

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
        return OCSeminarSeries::findOneBySeminar_id($course_id)->config_id;
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
        return OCSeminarSeries::findOneBySeries_id($series_id)->course_id;
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
        return OCSeminarSeries::findOneBySeminar_id($series_id)->config_id ?: 1;
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
        $stmt = \DBManager::get()->prepare("SELECT config_id
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

    /**
     * [getBaseServerConf description]
     *
     * @param  [type] $config_id [description]
     * @return [type]            [description]
     */
    static function getBaseServerConf($config_id = null)
    {
        if (is_null($config_id)) {
            return \SimpleCollection::createFromArray(
                self::findBySql('1 ORDER BY config_id ASC')
            )->toGroupedArray('config_id');
        }

        return self::find($config_id)->toArray();
    }
}
