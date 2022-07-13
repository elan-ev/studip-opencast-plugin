<?php

namespace Opencast\Middlewares;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

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
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request  das
     *                                                           PSR-7 Request-Objekt
     * @param \Psr\Http\Message\ResponseInterface      $response das PSR-7
     *                                                           Response-Objekt
     * @param callable                                 $next     das nächste Middleware-Callable
     *
     * @return \Psr\Http\Message\ResponseInterface das neue Response-Objekt
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function __invoke(Request $request, Response $response, $next)
    {
        $token = $request->getQueryParams()['token'];

        if (!empty($token) && $token == $this->api_token) {
            return $next($request, $response);
        }

        return $response->withStatus(401);
    }
}
