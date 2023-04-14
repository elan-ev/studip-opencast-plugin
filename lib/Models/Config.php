<?php
namespace Opencast\Models;

use Opencast\RelationshipTrait;
use Opencast\Models\UPMap;
use Opencast\Models\SeminarSeries;

class Config extends \SimpleOrMap
{
    use RelationshipTrait;

    protected const allowed_settings_fields = [
        'lti_consumerkey', 'lti_consumersecret', 'debug'
    ];

    protected static function configure($config = [])
    {
        $config['db_table'] = 'oc_config';

        $config['serialized_fields']['settings'] = 'JSONArrayObject';
        $config['registered_callbacks']['after_initialize'][] = 'sanitizeSettings';
        $config['registered_callbacks']['before_store'][]     = 'sanitizeSettings';

        parent::configure($config);
    }

    /**
     * function getConfigForService  - retries configutation for a given REST-Service-Client
     *
     * @param string $service_type - client label
     *
     * @return array configuration for corresponding client
     *
     */
    public static function getConfigForService($service_type, $config_id = 1)
    {
        if (isset($service_type)) {
            $config = Endpoints::findOneBySQL(
                'service_type = ? AND config_id = ?' ,
                [$service_type, $config_id]
            );

            if ($config) {
                return $config->toArray() + self::find($config_id)->toArray();
            } else {
                return false;
            }

        } else {
            throw new \Exception(_("Es wurde kein Servicetyp angegeben."));
        }
    }

    public function getRelationships()
    {
        return [];
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

        return self::find($config_id)->toArray();
    }

    /**
     * Return the complete configuration for the course
     *
     * @param string $course_id
     *
     * @return mixed  the configuration data for the passed course
     */
    public static function getConfigForCourse($course_id)
    {
        $oc_config = [];
        $config_id = self::getConfigIdForCourse($course_id);
        if ($config_id) {
            $oc_config = self::getBaseServerConf($config_id);
        }
        return $oc_config;
    }

    /**
     * get id of used config for the course
     *
     * @param string $course_id
     *
     * @return int
     */
    public static function getConfigIdForCourse($course_id)
    {
        return SeminarSeries::findOneBySeminar_id($course_id)->config_id;
    }

    public function sanitizeSettings($event)
    {
        foreach ($this->settings as $key => $value) {
            if (in_array($key, self::allowed_settings_fields) === false) {
                unset($this->settings[$key]);
            }
        }
    }
}
