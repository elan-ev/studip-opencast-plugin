<?php

namespace Opencast\Middlewares;

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;

class TokenAuthentication
{
    // the api token configured for this authentication strategy
    private $api_token;

    /**
     * Der Konstruktor.
     *
     * @param callable $authenticator ein Callable, das den
     *                                Nutzernamen und das Passwort als Argumente erhält und damit
     *                                entweder einen Stud.IP-User-Objekt oder null zurückgibt
     */
    public function __construct($api_token)
    {
        $this->api_token = $api_token;
    }

    /**
     * Hier muss die Autorisierung implementiert werden.
     */
    public function __invoke(Request $request, RequestHandler $handler)
    {
        $token = $request->getQueryParams()['token'];

        if (!empty($token) && $token == $this->api_token) {
            return $handler->handle($request);
        }

        $response = new Response();
        return $response->withStatus(401);
    }
}
