<?php
/**
 * OCRestClient.php - The administarion of the opencast player
 */

define(DEBUG_CURL, FALSE);

class OCRestClient
{
    static $me;
    protected $base_url;
    protected $username;
    protected $password;
    public $serviceName = 'ParentRestClientClass';

    static function getInstance($course_id = null)
    {
        $config_id = 1;     // use default config if nothing else is given

        if ($course_id) {
            $config_id = self::getConfigIdForCourse($course_id);
        }

        if (!property_exists(get_called_class(), 'me')) {
            throw new Exception('Every child of '.get_class().' needs to implement static property "$me"');
        }

        if (!is_object(static::$me[$config_id])) {
            static::$me[$config_id] = new static($config_id);
        }

        return static::$me[$config_id];
    }

    function __construct($config)
    {
        $this->base_url   = $config['service_url'];
        $this->username   = $config['service_user'];
        $this->password   = $config['service_password'];
        $this->oc_version = $config['service_version'];

        // setting up a curl-handler
        $this->ochandler = curl_init();
        curl_setopt($this->ochandler, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->ochandler, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
        curl_setopt($this->ochandler, CURLOPT_USERPWD, $this->username.':'.$this->password);
        curl_setopt($this->ochandler, CURLOPT_ENCODING, "UTF-8");
        curl_setopt($this->ochandler, CURLOPT_HTTPHEADER, array("X-Requested-Auth: Digest"));

        curl_setopt($this->ochandler, CURLOPT_FOLLOWLOCATION, 1);

        //ssl
        curl_setopt($this->ochandler, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($this->ochandler, CURLOPT_SSL_VERIFYHOST, false);
        #curl_setopt($this->ochandler, CURLOPT_SSL_CIPHER_LIST, 'RC4-SHA');

        // debugging
        if (DEBUG_CURL) {
            curl_setopt($this->ochandler, CURLOPT_VERBOSE, true);
            $this->debug = fopen('php://output', 'w');
            curl_setopt($this->ochandler, CURLOPT_STDERR, $this->debug);
        }
    }

    public function has_config_error()
    {
        return
            ($this->username == 'error' || $this->username == null) &&
            ($this->password == 'error' || $this->password == null) &&
            ($this->oc_version == 'error' || $this->oc_version == null) &&
            ($this->base_url == 'error' || $this->base_url == null);
    }

    /**
      * function getConfig  - retries configutation for a given REST-Service-Client
      *
      * @param string $service_type - client label
      *
      * @return array configuration for corresponding client
      *
      */
    function getConfig($service_type, $config_id = 1)
    {
        if (isset($service_type)) {
            $stmt = DBManager::get()->prepare("SELECT * FROM `oc_endpoints`
                WHERE service_type = ? AND config_id = ?");
            $stmt->execute(array($service_type, $config_id));
            $config = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($config) {
                $stmt = DBManager::get()->prepare("SELECT * FROM `oc_config`
                    WHERE config_id = ?");
                $stmt->execute(array($config_id));
                $config = $config + $stmt->fetch(PDO::FETCH_ASSOC);
                return $config;
            } else {
                return [
                    $this->empty_config()
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
     *  @param string $service_url
     *  @param string $service_user
     *  @param string $service_password
     */
    function setConfig($config_id = 1, $service_url, $service_user, $service_password, $version)
    {
        if (isset($service_url, $service_user, $service_password, $version)) {
            $stmt = DBManager::get()->prepare('REPLACE INTO `oc_config`
                (config_id, service_url, service_user, service_password, service_version)
                VALUES (?,?,?,?,?)'
            );

            return $stmt->execute([
                $config_id, $service_url, $service_user,
                $service_password, (int)$version
            ]);
        } else {
            throw new Exception(_('Die Konfigurationsparameter wurden nicht korrekt angegeben.'));
        }

    }

    function clearConfig($config_id) {
        $stmt = DBManager::get()->prepare("DELETE FROM `oc_config` WHERE config_id = ?;");
        $stmt->execute(array($config_id));
        $stmt = DBManager::get()->prepare("DELETE FROM `oc_endpoints` WHERE config_id = ?;");
        return $stmt->execute(array($config_id));
    }

    /**
     *  function getJSON - performs a REST-Call and retrieves response in JSON
     */
    function getJSON($service_url, $data = array(), $is_get = true, $with_res_code = false)
    {
        if (isset($service_url)) {
            $options = array(
                CURLOPT_URL => $this->base_url.$service_url,
                CURLOPT_FRESH_CONNECT => 1
            );

            if (!$is_get) {
                $options[CURLOPT_POST] = 1;
                if (!empty($data)) {
                    $options[CURLOPT_POSTFIELDS] = $data;
                }
            } else {
                $options[CURLOPT_HTTPGET] = 1;
            }

            curl_setopt_array($this->ochandler, $options);
            $response = curl_exec($this->ochandler);
            $httpCode = curl_getinfo($this->ochandler, CURLINFO_HTTP_CODE);

            if (DEBUG_CURL) {
                fclose($this->debug);
            }

            if ($with_res_code) {
                return array(json_decode($response) ?: $response, $httpCode);
            } else {
                // throw exception if the endpoint is missing
                if ($httpCode == 404) {
                    if (DEBUG_CURL) {
                        error_log('[Opencast-Plugin] Error calling "'
                            . $this->base_url.$service_url
                            .'" ' . strip_tags($response)
                        );
                    }

                    return false;
                } else {
                    return json_decode($response);
                }
            }
        } else {
            throw new Exception(_("Es wurde keine Service URL angegeben"));
        }

    }

    /**
     * function getXML - performs a REST-Call and retrieves response in XML
     */
    function getXML($service_url, $data = array(), $is_get = true, $with_res_code = false)
    {
        if (isset($service_url)) {
            $options = array(
                CURLOPT_URL => $this->base_url.$service_url,
                CURLOPT_FRESH_CONNECT => 1
            );

            if (!$is_get) {
                $options[CURLOPT_POST] = 1;
                if (!empty($data)) {
                    $options[CURLOPT_POSTFIELDS] = $data;
                }
            } else {
                $options[CURLOPT_HTTPGET] = 1;
            }

            curl_setopt_array($this->ochandler, $options);
            $response = curl_exec($this->ochandler);
            $httpCode = curl_getinfo($this->ochandler, CURLINFO_HTTP_CODE);

            if ($with_res_code) {
                return array($response, $httpCode);
            } else {
                // throw exception if the endpoint is missing
                if ($httpCode == 404) {
                    if (DEBUG_CURL) {
                        error_log('[Opencast-Plugin] Error calling "'
                            . $this->base_url.$service_url
                            .'" ' . strip_tags($response)
                        );
                    }

                    return false;
                } else {
                    return $response;
                }
            }
        } else {
            throw new Exception(_("Es wurde keine Service URL angegeben"));
        }
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

        $stmt->execute(array($course_id));

        return $stmt->fetchColumn() ?: 1;
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

        $stmt->execute(array($series_id));

        return $stmt->fetchColumn() ?: 1;
    }

    /**
     * get course-id for passed series
     *
     * @param string $series_id
     *
     * @return string
     */

    static function getCourseIdForWorkflow($workflow_id)
    {
        $stmt = DBManager::get()->prepare("SELECT seminar_id
            FROM oc_seminar_workflows
            WHERE workflow_id = ?");

        $stmt->execute(array($workflow_id));

        return $stmt->fetchColumn() ?: 1;
    }

    public function empty_config()
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
