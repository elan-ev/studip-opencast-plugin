<?php
namespace Opencast\Models;

use Opencast\RelationshipTrait;
use Opencast\Models\UPMap;
use Opencast\Models\SeminarSeries;
use Opencast\Models\REST\Config as RESTConfig;
use Opencast\Models\REST\ServicesClient;
use Opencast\Models\WorkflowConfig;

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

    /**
     * Update settings with the passed settings
     *
     * @param Array $json the settings to update, with workflows
     * @return void
     */
    public function updateSettings($json)
    {
        $new_settings = [];
        $stored_config = $this->toArray();
        foreach ($json as $setting_name => $setting) {
            if (!in_array($setting_name, array_keys($stored_config)) && $setting_name != 'checked') {
                $new_settings[$setting_name] = $setting;
            }
        }

        $json['settings'] = $new_settings;

        // save configured workflows to store them when installation is successfull
        $workflows = [];
        if (isset($json['settings']['workflow_configs'])) {
            foreach ($json['settings']['workflow_configs'] as $wf_config) {
                $workflows[$wf_config['id']] = $wf_config;
            }
            unset($json['settings']['workflow_configs']);
        }

        $this->setData($json);
        return $this->store();
    }

    /**
     * load and update endpoihnts for reference OC server
     *
     * @param Container $container
     * @return void
     */
    public function updateEndpoints($container)
    {
        $service_url =  parse_url($this->service_url);

        // check the selected url for validity
        if (!array_key_exists('scheme', $service_url)) {
            $message = [
                'type' => 'error',
                'text' => sprintf(
                    _('Ungültiges URL-Schema: "%s"'),
                    $this->service_url
                )
            ];

            Endpoints::deleteBySql('config_id = ?', [$this->id]);
            Config::deleteBySql('id = ?', [$this->id]);
        } else {
            $service_host =
                $service_url['scheme'] .'://' .
                $service_url['host'] .
                (isset($service_url['port']) ? ':' . $service_url['port'] : '');

            try {
                $version = RESTConfig::getOCBaseVersion($this->id);

                Endpoints::deleteBySql('config_id = ?', [$this->id]);

                $this->service_version = $version;
                $this->store();

                Endpoints::setEndpoint($this->id, $service_host .'/services', 'services');

                $services_client = new ServicesClient($this->id);

                $comp = null;
                $comp = $services_client->getRESTComponents();
            } catch (AccessDeniedException $e) {
                Endpoints::removeEndpoint($this->id, 'services');

                $message = [
                    'type' => 'error',
                    'text' => sprintf(
                        _('Fehlerhafte Zugangsdaten für die Opencast Installation mit der URL "%s". Überprüfen Sie bitte die eingebenen Daten.'),
                        $service_host
                    )
                ];

                $this->redirect('admin/config');
                return;
            }

            if ($comp) {
                $services = RESTConfig::retrieveRESTservices($comp, $service_url['scheme']);

                if (empty($services)) {
                    Endpoints::removeEndpoint($this->id, 'services');
                    $message = [
                        'type' => 'error',
                        'text' => sprintf(
                            _('Es wurden keine Endpoints für die Opencast Installation mit der URL "%s" gefunden. '
                                . 'Überprüfen Sie bitte die eingebenen Daten, achten Sie dabei auch auf http vs https und '
                                . 'ob ihre Opencast-Installation https unterstützt.'),
                            $service_host
                        )
                    ];
                } else {

                    foreach($services as $service_url => $service_type) {
                        if (in_array(
                                strtolower($service_type),
                                $container['opencast']['services']
                            ) !== false
                        ) {
                            Endpoints::setEndpoint($this->id, $service_url, $service_type);
                        } else {
                            unset($services[$service_url]);
                        }
                    }

                    // create new entries for workflow_config table
                    WorkflowConfig::createAndUpdateByConfigId($this->id, $workflows);

                    $success_message[] = sprintf(
                        _('Die Opencast Installation "%s" wurde erfolgreich konfiguriert.'),
                        $service_host
                    );

                    $message = [
                        'type' => 'success',
                        'text' => implode('<br>', $success_message)
                    ];

                    $config_checked = true;
                }
            } else {
                $message = [
                    'type' => 'error',
                    'text' => sprintf(
                        _('Es wurden keine Endpoints für die Opencast Installation mit der URL "%s" gefunden. Überprüfen Sie bitte die eingebenen Daten.'),
                        $service_host
                    )
                ];
            }
        }

        return $message;
    }
}
