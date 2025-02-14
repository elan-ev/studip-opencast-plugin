<?php

namespace Opencast\Routes\Config;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\Helpers;
use Opencast\Models\Config;
use Opencast\Models\LTI\LtiHelper;
use Opencast\Errors\Error;

class ConfigEdit extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        \SimpleOrMap::expireTableScheme();

        $json = $this->getRequestData($request);

        $duplicate_url = false;

        $config = Config::find($args['id']);
        $config_old = $config->toArray();

        if (empty($config)) {
            throw new Error('Could not find config with id '. $args['id'] .'.', 500);
        }

        // check settings and store them to the database
        $config->updateSettings($json['config']);

        // check configuration and load endpoints
        $message = $config->updateEndpoints();
        // Restore configuration if it failed
        if ($message['type'] == 'error') {
            $config->setData($config_old);
            $config->store();
        }

        // Validate that a correct default server is set
        Helpers::validateDefaultServer();

        $ret_config = $config->toArray();
        $ret_config = array_merge($ret_config, $ret_config['settings']);
        unset($ret_config['settings']);

        if ($message['type'] == 'success') {
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
