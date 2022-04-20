<?php

namespace Opencast\Models;

use Opencast\Configuration;

class OCConfig extends \SimpleORMap
{
    protected static function configure($config = [])
    {
        $config['db_table'] = 'oc_config';

        $config['has_many']['endpoints'] = [
            'class_name'        => 'Opencast\\Models\\OCEndpoints',
            'assoc_foreign_key' => 'config_id',
            'on_delete'         => 'delete'
        ];

        $config['serialized_fields']['settings'] = 'JSONArrayObject';

        parent::configure($config);
    }

    /**
     * Return the complete configuration for the passed course
     *
     * @param string $course_id
     *
     * @return mixed  the configuration data for the passed course
     */
    public static function getConfigForCourse($course_id)
    {
        static $config;

        if (!$config[$course_id]) {
            $config_id = self::getConfigIdForCourse($course_id);
            if ($config_id) {
                $settings  = Configuration::instance($config_id);
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
    public static function getConfigForService($service_type, $config_id = 1, $force_endpoint = '')
    {
        if (isset($service_type)) {
            $config = OCEndpoints::findOneBySQL(
                'service_type = ? AND config_id = ?',
                [$service_type, $config_id]
            );

            if ($config) {
                return $config->toArray() + self::find($config_id)->toArray();
            } else if (!empty($force_endpoint)) {
                // Make sure all defined endpoint serives have their records in OCEndpoints when they have $force_endpoint!
                // came across this issue that /api/service has been included in /service call!
                $base_config = self::find($config_id)->toArray();
                $service_url = $base_config['service_url'] . '/' . ltrim($force_endpoint, '/');
                OCEndpoints::setEndpoint($config_id, $service_url, $service_type);
                $config = OCEndpoints::findOneBySQL(
                    'service_type = ? AND config_id = ?',
                    [$service_type, $config_id]
                );
                return $config->toArray() + $base_config;
            } else {
                // TODO: In case a config empty, it has to be empty array instead of empty_config in order
                // for the rest of the code to catch the error/exception!
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
     * @param int $config_id
     * @param string $service_url
     * @param string $service_user
     * @param string $service_password
     * @param string $version
     *
     * @return
     * @throws Exception
     */
    public static function setConfig($id = 1, $service_url, $service_user, $service_password, $version)
    {
        if (isset($service_url, $service_user, $service_password, $version)) {
            if (!$config = self::find($id)) {
                $config = new self();
            }

            $service_version = (int)$version;

            $config->setData(compact('id', 'service_url',
                'service_user', 'service_password', 'service_version'));
            return $config->store();
        } else {
            throw new \Exception(_('Die Konfigurationsparameter wurden nicht korrekt angegeben.'));
        }
    }

    public static function clearConfigAndAssociatedEndpoints($config_id)
    {
        return self::deleteBySql('id = ?', [$config_id]);
    }

    /**
     * get id of used config for passed course
     *
     * @param string $course_id
     *
     * @return int
     */
    public static function getConfigIdForCourse($course_id)
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

    public static function getCourseIdForSeries($series_id)
    {
        return OCSeminarSeries::findOneBySeries_id($series_id)->seminar_id;
    }

    /**
     * get config-id for passed series
     *
     * @param string $series_id
     *
     * @return string
     */

    public static function getConfigIdForSeries($series_id)
    {
        return OCSeminarSeries::findOneBySeries_id($series_id)->config_id ?: 1;
    }

    public static function empty_config()
    {
        return [
            'id'        => 'error',
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
    public static function getBaseServerConf($config_id = null)
    {
        if (is_null($config_id)) {
            return \SimpleCollection::createFromArray(
                self::findBySql('1 ORDER BY id ASC')
            )->toGroupedArray('id');
        }
        $config = self::find($config_id);
        return (!empty($config)) ? $config->toArray() : null;
    }
}
