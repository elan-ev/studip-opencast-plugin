<?php
/**
 *  Opencast\Models\REST\RestClient.php - The REST Client for Opencast
 */
namespace Opencast\Models\REST;

use Opencast\Models\Config;
use Opencast\Models\Endpoints;
use Opencast\Errors\RESTError;
use \Context;

class RestClient
{
    static $me;

    protected $base_url,
        $username,
        $password,
        $oc_version,
        $config_id,
        $debug = false,
        $cookie;

    const
        GET = 1,
        POST = 2,
        PUT = 3,
        DELETE = 4;

    public $serviceName = 'ParentRestClientClass';

    static function getInstance($config_id = null)
    {
        // use default config if nothing else is given
        if (is_null($config_id) || $config_id === false) {
            $config_id = Config::getConfigIdForCourse(Context::getId()) ?: 1;
        }

        if (!property_exists(get_called_class(), 'me')) {
            throw new RESTError('Every child of ' . get_class() . ' needs to implement static property "$me"');
        }

        if (!is_object(static::$me[$config_id])) {
            static::$me[$config_id] = new static($config_id);
        }

        return static::$me[$config_id];
    }

    static function create($course_id = null)
    {
        if (is_null($course_id)) {
            $course_id = Context::getId();
        }

        $config_id = Config::getConfigIdForCourse($course_id) ?: 1;

        return self::getInstance($config_id);
    }

    function __construct($config_id, $service_type = null)
    {
        if ($config = Config::find($config_id)) {
            $this->config_id  = $config->id;
            $this->base_url   = $config->service_url;

            if ($service_type) {
                if ($endpoint = Endpoints::findBySql(
                    'config_id = ? AND service_type = ?', [$config_id, $service_type]
                )) {
                    $this->base_url = $endpoint[0]->service_url;
                } else {
                    throw new RESTError(_("Konnte keine Konfiguration für den Service $service_type  finden."));
                }
            }

            $this->username   = $config->service_user;
            $this->password   = $config->service_password;
            $this->oc_version = $config->service_version;
            $this->debug      = $config->settings->debug ? true : false;
        } else {
            throw new RESTError(_("Die Konfiguration wurde nicht korrekt angegeben."));
        }
    }

    private function initCurl()
    {
        // setting up a curl-handler
        $this->ochandler = curl_init();
        curl_setopt($this->ochandler, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->ochandler, CURLOPT_USERPWD, $this->username . ':' . $this->password);
        curl_setopt($this->ochandler, CURLOPT_ENCODING, 'UTF-8');

        //curl_setopt($this->ochandler, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
        //curl_setopt($this->ochandler, CURLOPT_HTTPHEADER, ["X-Requested-Auth: Digest"]);

        curl_setopt($this->ochandler, CURLOPT_FOLLOWLOCATION, 1);

        //ssl
        curl_setopt($this->ochandler, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($this->ochandler, CURLOPT_SSL_VERIFYHOST, false);
        /*
        if ($precise_config['ssl_cipher_list'] != 'none') {
            curl_setopt($this->ochandler, CURLOPT_SSL_CIPHER_LIST, $precise_config['ssl_cipher_list']);
        }
        */

        // debugging
        if ($this->debug) {
            curl_setopt($this->ochandler, CURLOPT_VERBOSE, true);
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

    function setCookie($name, $value)
    {
        $this->cookie = $name .'='. $value;
    }

    function getCookie()
    {
        return $this->cookie;
    }

    function putJSON($service_url, $data = [], $with_res_code = false)
    {
        return $this->jsonRequest($service_url, $data, $with_res_code, self::PUT);
    }

    function postJSON($service_url, $data = [], $with_res_code = false)
    {
        return $this->jsonRequest($service_url, $data, $with_res_code, self::POST);
    }

    function getJSON($service_url, $data = [], $is_get = true, $with_res_code = false)
    {
        if ($is_get) {
            return $this->jsonRequest($service_url, [], $with_res_code, self::GET);
        } else {
            return $this->jsonRequest($service_url, $data, $with_res_code, self::POST);
        }
    }

    function deleteJSON($service_url, $with_res_code = false)
    {
        return $this->jsonRequest($service_url, [], $with_res_code, self::DELETE);
    }

    /**
     *  function getJSON - performs a REST-Call and retrieves response in JSON
     */
    function jsonRequest($service_url, $data = [], $with_res_code = false, $type)
    {
        if (isset($service_url)) {
            $this->initCurl();

            if ($this->debug) {
                echo '<pre>';
                var_dump($data);
                $this->debug = fopen('php://output', 'w');
                curl_setopt($this->ochandler, CURLOPT_STDERR, $this->debug);
            }

            $options = [
                CURLOPT_URL           => $this->base_url . $service_url,
                CURLOPT_FRESH_CONNECT => 1
            ];

            if ($type == self::POST) {
                $options[CURLOPT_POST] = 1;
                if (!empty($data)) {
                    $options[CURLOPT_POSTFIELDS] = $data;
                }
            } else if ($type == self::GET) {
                $options[CURLOPT_HTTPGET] = 1;
            } else if ($type == self::PUT) {
                $options[CURLOPT_CUSTOMREQUEST] = 'PUT';
                if (!empty($data)) {
                    $options[CURLOPT_POSTFIELDS] = $data;
                }
            } else if ($type == self::DELETE) {
                $options[CURLOPT_CUSTOMREQUEST] = 'DELETE';
            }


            if (!$this->debug) {  // CURLINFO_HEADER_OUT ist not worjing in conjunction with CURLOPT_VERBOSE
                // see: https://bugs.php.net/bug.php?id=65348
                curl_setopt($this->ochandler, CURLINFO_HEADER_OUT, true);
            }
            curl_setopt_array($this->ochandler, $options);


            if ($this->getCookie()) {
                curl_setopt($this->ochandler, CURLOPT_HTTPHEADER, ['Cookie: '. $this->getCookie()]);
            }

            $response = curl_exec($this->ochandler);
            $httpCode = curl_getinfo($this->ochandler, CURLINFO_HTTP_CODE);

            if ($this->debug) {
                fclose($this->debug);
                echo '</pre>';
            }

            if ($with_res_code) {
                return [json_decode($response, true) ? : $response, $httpCode];
            } else {
                // throw exception if the endpoint is missing
                if ($httpCode == 404) {
                    if ($this->debug) {
                        error_log('[Opencast-Plugin] Error calling "'
                            . $this->base_url . $service_url
                            . '" ' . strip_tags($response)
                        );
                    }

                    return false;
                } else if ($httpCode == 401) {
                    throw new RESTError('Wrong username/password for Opencast server!');
                } else {
                    return json_decode($response, true);
                }
            }
        } else {
            throw new RESTError(_("Es wurde keine Service URL angegeben"));
        }

    }

    /**
     * function getXML - performs a REST-Call and retrieves response in XML
     */
    function getXML($service_url, $data = [], $is_get = true, $with_res_code = false)
    {
        if (isset($service_url)) {
            $this->initCurl();

            if ($this->debug) {
                echo '<pre>';
                var_dump($data);
                $this->debug = fopen('php://output', 'w');
                curl_setopt($this->ochandler, CURLOPT_STDERR, $this->debug);
            }

            $options = [
                CURLOPT_URL           => $this->base_url . $service_url,
                CURLOPT_FRESH_CONNECT => 1
            ];

            if (!$is_get) {
                $options[CURLOPT_POST] = 1;
                if (!empty($data)) {
                    $options[CURLOPT_POSTFIELDS] = $data;
                }
            } else {
                $options[CURLOPT_HTTPGET] = 1;
            }

            if ($this->getCookie()) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Cookie: '. $this->getCookie()]);
            }

            curl_setopt_array($this->ochandler, $options);
            $response = curl_exec($this->ochandler);
            $httpCode = curl_getinfo($this->ochandler, CURLINFO_HTTP_CODE);

            if ($this->debug) {
                fclose($this->debug);
                echo '</pre>';
            }

            if ($with_res_code) {
                return [$response, $httpCode];
            } else {
                // throw exception if the endpoint is missing
                if ($httpCode == 404) {
                    if ($this->debug) {
                        error_log('[Opencast-Plugin] Error calling "'
                            . $this->base_url . $service_url
                            . '" ' . strip_tags($response)
                        );
                    }

                    return false;
                } else {
                    return $response;
                }
            }
        } else {
            throw new RESTError(_("Es wurde keine Service URL angegeben"));
        }
    }
}
