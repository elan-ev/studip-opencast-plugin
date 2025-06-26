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

    const MAINTENANCE_MODE_OFF = 'off';
    const MAINTENANCE_MODE_ON = 'on';
    const MAINTENANCE_MODE_READONLY = 'read-only';

    protected static function configure($config = [])
    {
        $config['db_table'] = 'oc_config';

        $config['serialized_fields']['settings'] = 'JSONArrayObject';
        $config['registered_callbacks']['after_initialize'][] = 'sanitizeSettings';
        $config['registered_callbacks']['before_store'][]     = 'sanitizeSettings';

        parent::configure($config);
    }

    /**
     * Returns the merged configuration and endpoint data for a given service type and config ID.
     *
     * Retrieves the Opencast configuration and endpoint for the specified service type and configuration ID,
     * taking maintenance mode into account. Returns false if configuration or endpoint is missing,
     * or null if access is not allowed due to maintenance restrictions.
     *
     * @param string $service_type The type of Opencast service (e.g., 'ingest', 'search').
     * @param int $config_id The configuration ID (default: 1).
     * @return array|false|null Merged configuration and endpoint data, false if not found, or null if not accessible.
     * @throws \Exception In case something goes wrong!
     */
    public static function getConfigForService($service_type, $config_id = 1)
    {
        if (isset($service_type)) {
            $result = [];

            $oc_config = self::find($config_id);
            $endpoint_config = Endpoints::findOneBySQL(
                'service_type = ? AND config_id = ?' ,
                [$service_type, $config_id]
            );

            if (empty($oc_config) || empty($endpoint_config)) {
                return false;
            }

            list($maintenance_on, $maintenance_readonly) = $oc_config->isUnderMaintenance();

            $can_access = !$maintenance_on ||
                ($maintenance_readonly && in_array($service_type, RESTConfig::ENGAGE_NODE_SERVICE_TYPES));

            if (!$can_access) {
                return null;
            }

            $oc_config_array = $oc_config->toArray();
            $endpoint_config_array = $endpoint_config->toArray();

            // Here we need to replace the service_url with the maintenance_engage_url_fallback (1. priority) if provided in config
            // or the endpoint service_url (2. priority)
            if ($maintenance_readonly) {
                $replacing_server_url = $oc_config_array['maintenance_engage_url_fallback'];
                if (empty($replacing_server_url)) {
                    $replacing_server_url = $endpoint_config_array['service_url'];
                }
                $replacing_server_url_parsed = parse_url($replacing_server_url);

                $replacing_server_url_clean = $replacing_server_url_parsed['scheme'] . '://'. $replacing_server_url_parsed['host']
                    . (isset($replacing_server_url_parsed['port']) ? ':' . $replacing_server_url_parsed['port'] : '');

                $oc_config_array['service_url'] = $replacing_server_url_clean;
            }

            $result = $endpoint_config_array + $oc_config_array;

            return $result;
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

        list($maintenance_on, $maintenance_readonly) = $this->isUnderMaintenance();

        // Prevent updating endpoints if the server is under maintenance.
        if ($maintenance_on) {
            $message = [
                'type' => 'warning',
                'text' => _('Diese Opencast-Instanz ist derzeit im Wartungsmodus, daher können Endpunkte nicht aktualisiert werden.')
            ];
            return $message;
        }

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

                $custom_config = [
                    'config_id'        => $this->id,
                    'service_url'      => $service_host,
                    'service_user'     => $this->service_user,
                    'service_password' => $this->service_password,
                    'service_version'  => $this->service_version,
                    'settings'         => [
                        'ssl_ignore_cert_errors' => $this->settings['ssl_ignore_cert_errors']
                    ]
                ];

                $services_client = new ServicesClient($this->id, $custom_config);

                $comp = null;
                try {
                    $comp = $services_client->getRESTComponents();
                }
                catch (\Exception $e) {
                    if (str_starts_with($e->getMessage(), 'cURL error')) {
                        return [
                            'type' => 'error',
                            'text' => sprintf(
                                _('Die angegebene URL %s konnte nicht gefunden werden. Überprüfen Sie bitte ihre Eingabe und versuchen Sie es erneut.'),
                                $service_host
                            ) . " -> " . $e->getMessage()
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
                return [
                    'type' => 'error',
                    'text' => sprintf(
                        _('Fehlerhafte Zugangsdaten für die Opencast-Installation mit der URL "%s". Überprüfen Sie bitte die eingegebenen Daten.'),
                        $service_host
                    )
                ];
            }

            if ($comp) {
                $services = RESTConfig::retrieveRESTservices($comp, $service_url);

                if (empty($services)) {
                    $message = [
                        'type' => 'error',
                        'text' => sprintf(
                            _('Es wurden keine Endpoints für die Opencast-Installation mit der URL "%s" gefunden. '
                                . 'Überprüfen Sie bitte die eingegebenen Daten. Achten Sie dabei auch auf http vs. https und '
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
                    WorkflowConfig::createAndUpdateByConfigId($this->id);

                    $success_message[] = sprintf(
                        _('Die Opencast-Installation "%s" wurde erfolgreich konfiguriert.'),
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
                        _('Es wurden keine Endpoints für die Opencast-Installation mit der URL "%s" gefunden. Überprüfen Sie bitte die eingebenen Daten.'),
                        $service_host
                    )
                ];
            }
        }

        return $message;
    }

    /**
     * Checks if the Opencast instance is in maintenance mode.
     *
     * @param bool $with_keys Whether to return an associative array with keys ('active', 'read_only').
     * @return array [maintenance_on, maintenance_readonly] or ['active' => bool, 'read_only' => bool] if $with_keys is true.
     */
    public function isUnderMaintenance($with_keys = false)
    {
        $maintenance_on = ($this->maintenance_mode === self::MAINTENANCE_MODE_ON
            || $this->maintenance_mode === self::MAINTENANCE_MODE_READONLY);

        $res = [
            'active' => $maintenance_on,
            'read_only' => $this->maintenance_mode === self::MAINTENANCE_MODE_READONLY
        ];
        return $with_keys ? $res : array_values($res);
    }
}
