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
use Opencast\Models\LTI\LtiHelper;

use Opencast\Models\I18N as _;

class ConfigAdd extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        \SimpleOrMap::expireTableScheme();

        $json = $this->getRequestData($request);

        $config_checked = false;
        $duplicate_url = false;

        // check, if a config with the same data already exists:
        $config = reset(Config::findBySql('service_url = ?', [$json['config']['service_url']]));

        // POST request - create config
        if ($config) {
            $duplicate_url = true;
        } else {
            $config = new Config;
        }

        // Throw error if the url is already used
        if ($duplicate_url) {
            return $this->createResponse([
                'message'=> [
                    'type' => 'error',
                    'text' => sprintf(
                        _('Eine Konfiguration mit der angegebenen URL ist bereits vorhanden: "%s"'),
                        $json['config']['service_url']
                    )
                ],
            ], $response);
        }

        // check settings and store them to the database
        $config->updateSettings($json['config']);

        // check configuration and load endpoints
        $message = $config->updateEndpoints($this->container);

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
