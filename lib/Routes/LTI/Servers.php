<?php

namespace Opencast\Routes\LTI;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\Config;
use Opencast\Models\LTI\LtiHelper;

class Servers extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        $config_list = Config::findBySql(1);

        $results = [];

        foreach ($config_list as $config) {
            $results[] = [
                'id'              => $config['id'],
                'service_url'     => $config['service_url'],
                'service_version' => $config['service_version'],
                'lti'             => LtiHelper::getLaunchDataForCourse($config->id, \Context::getId())
            ];
        }

        return $this->createResponse(['servers' => $results], $response);
    }
}
