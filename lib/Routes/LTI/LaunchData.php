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

class LaunchData extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        $configs = Config::findBySql(1);

        $context_id = isset($args['context_id']) ? $args['context_id'] : \Context::getId();

        if (!empty($configs)) {
            $links = [];
            foreach ($configs as $config) {
                $links = array_merge($links, LtiHelper::getLaunchDataForCourse($config->id, $context_id));
            }
            return $this->createResponse(['lti' => $links], $response);
        } else {
            return $this->createResponse(['lti' => []], $response);
        }
    }
}
