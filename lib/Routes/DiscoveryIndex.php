<?php

namespace Opencast\Routes;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Opencast\OpencastController;
use Opencast\OpencastTrait;

class DiscoveryIndex extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        $routes = $this->container->get('router')->getRoutes();

        foreach ($routes as $id => $route) {
            $data[] = [
                'type' => 'slim-routes',
                'id'   => $id,
                'attributes' => [
                    'methods' => $route->getMethods(),
                    'pattern' => $route->getPattern()
                ]
            ];
        }
        return $this->createResponse(['data' => $data], $response);
    }
}
