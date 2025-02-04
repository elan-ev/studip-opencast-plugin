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

    protected const ALLOWED_SETTINGS_FIELDS = [
        'lti_consumerkey', 'lti_consumersecret', 'debug', 'ssl_ignore_cert_errors', 'episode_id_role_access'
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
        if (is_null($config_id) || $config_id == 0) {
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
            if (in_array($key, self::ALLOWED_SETTINGS_FIELDS) === false) {
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
        WorkflowConfig::createAndUpdateByConfigId($this->id, $workflows);

        // Update workflow settings
        $workflow_settings = [];
        if (isset($json['settings']['workflow_settings'])) {
            foreach ($json['settings']['workflow_settings'] as $wf_setting) {
                $workflow_settings[$wf_setting['id']] = $wf_setting;
            }
            unset($json['settings']['workflow_settings']);
        }
        Workflow::updateSettings($this->id, $workflow_settings);

        $this->setData($json);
        return $this->store();
    }

    /**
     * load and update endpoihnts for reference OC server
     *
     * @return void|array message
     */
    public function updateEndpoints()
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
        } else {
            $service_host =
                $service_url['scheme'] .'://' .
                $service_url['host'] .
                (isset($service_url['port']) ? ':' . $service_url['port'] : '');

            try {
                $version = RESTConfig::getOCBaseVersion($this->id);

                $this->service_version = $version;
                $this->store();

                Endpoints::setEndpoint($this->id, $service_host .'/services', 'services');

                $services_client = new ServicesClient($this->id);

                $comp = null;
                try {
                    $comp = $services_client->getRESTComponents();
                }
                catch(\Exception $e) {
                    if (str_starts_with($e->getMessage(), 'cURL error 6')) {
                        return [
                            'type' => 'error',
                            'text' => sprintf(
                                _('Die angegebene URL %s konnte nicht gefunden werden. Überprüfen Sie bitte ihre Eingabe und versuchen Sie es erneut.'),
                                $service_host
                            )
                        ];
                    }
                    else {
                        return [
                            'type' => 'error',
                            'text' => sprintf(
                                _('%s'),
                                $e->getMessage()
                            )
                        ];
                    }
                }
            } catch (AccessDeniedException $e) {
                Endpoints::removeEndpoint($this->id, 'services');

                return [
                    'type' => 'error',
                    'text' => sprintf(
                        _('Fehlerhafte Zugangsdaten für die Opencast Installation mit der URL "%s". Überprüfen Sie bitte die eingegebenen Daten.'),
                        $service_host
                    )
                ];
            }

            if ($comp) {
                $services = RESTConfig::retrieveRESTservices($comp, $service_url);

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
                    // clear the endpoints before setting the new ones to get rid of obsolete ones
                    Endpoints::deleteBySql('config_id = ?', [$this->id]);

                    foreach($services as $service_url => $service_type) {
                        Endpoints::setEndpoint($this->id, $service_url, $service_type);
                    }

                    // create new entries for workflow_config table
                    WorkflowConfig::createAndUpdateByConfigId($this->id, $workflows);

                    $success_message[] = sprintf(
                        _('Die Opencast Installation "%s" wurde erfolgreich konfiguriert.'),
                        $service_host
                    );

                    $message = [
                        'type' => 'success',
                        'text' => implode('<br>', (array)$success_message)
                    ];
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
