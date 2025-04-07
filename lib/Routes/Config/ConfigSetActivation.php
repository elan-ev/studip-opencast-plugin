<?php

namespace Opencast\Routes\Config;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\Config;

class ConfigSetActivation extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        $config = Config::find($args['id']);
        $active = $args['activation'] == 'activate';


        $config->active = $active ? 1 : 0;
        $config->store();

        if ($active) {
            $message = sprintf(_('Der Server wurde ##%s aktiviert.'), $args['id']);
        } else {
            $message = sprintf(_('Der Server wurde ##%s deaktiviert.'), $args['id']);
        }

        return $this->createResponse([
            'message'=> [
                'type' => 'success',
                'text' => $message
            ]
        ], $response);
    }
}
