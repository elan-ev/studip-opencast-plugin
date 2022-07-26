<?php

namespace Opencast\Routes\Config;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Models\Config;
use Opencast\Models\Endpoints;
use Opencast\Models\SeminarEpisodes;
use Opencast\Models\LTI\LtiLink;
use Opencast\Models\LTI\LtiHelper;
use Opencast\Models\REST\Config as RESTConfig;
use Opencast\Models\REST\ServicesClient;

use Opencast\Models\I18N as _;

class ConfigAddEdit extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        $constants = $this->container->get('opencast');
        \SimpleOrMap::expireTableScheme();

        $json = $this->getRequestData($request);

        $config_checked = false;
        // check, if a config with the same data already exists:
        if ($args['id']) {
            $config = Config::find($args['id']);
        } else {
            $config = reset(Config::findBySql('service_url = ?', [$json['config']['service_url']]));

            if (!$config) {
                $config = new Config;
            }
        }

        // validate values
        $new_settings = [];
        foreach ($constants['global_config_options'] as $config_entry) {
            foreach ($json['config'] as $setting_name => $setting) {
                if ($setting_name == $config_entry['name']) {
                    $new_settings[$setting_name] = $setting;
                    unset($json['config'][$setting_name]);
                }
            }
        }

        $json['config']['settings'] = $new_settings;

        // store config to database
        $config->setData($json['config']);
        $config->store();

        // check Configuration and load endpoints
        $message = null;

        $service_url =  parse_url($config->service_url);

        // check the selected url for validity
        if (!array_key_exists('scheme', $service_url)) {
            $message = [
                'type' => 'error',
                'text' => sprintf(
                    _('Ungültiges URL-Schema: "%s"'),
                    $config->service_url
                )
            ];

            Endpoints::deleteBySql('config_id = ?', [$config->id]);
        } else {
            $service_host =
                $service_url['scheme'] .'://' .
                $service_url['host'] .
                (isset($service_url['port']) ? ':' . $service_url['port'] : '');

            try {
                $version = RESTConfig::getOCBaseVersion($config->id);

                Endpoints::deleteBySql('config_id = ?', [$config->id]);

                $config->service_version = explode('.', $version)[0];
                $config->store();

                Endpoints::setEndpoint($config->id, $service_host .'/services', 'services');

                $services_client = new ServicesClient($config->id);

                $comp = null;
                $comp = $services_client->getRESTComponents();
            } catch (AccessDeniedException $e) {
                Endpoints::removeEndpoint($config->id, 'services');

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
                    Endpoints::removeEndpoint($config->id, 'services');
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
                                $this->container['opencast']['services']
                            ) !== false
                        ) {
                            Endpoints::setEndpoint($config->id, $service_url, $service_type);
                        } else {
                            unset($services[$service_url]);
                        }
                    }

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

        $ret_config = $config->toArray();
        $ret_config = array_merge($ret_config, $ret_config['settings']);
        unset($ret_config['settings']);

        if ($config_checked) {
            $lti = LtiHelper::getLaunchData($config->id);

            return $this->createResponse([
                'config' => $ret_config,
                'message'=> $message,
                'lti' => $lti
            ], $response);
        }

        return $this->createResponse([
            'config' => $ret_config,
            'message'=> $message,
        ], $response);
    }
}
