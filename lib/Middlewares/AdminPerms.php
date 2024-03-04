<?php

namespace Opencast\Middlewares;

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Opencast\Errors\Error;

class AdminPerms
{
    // the container
    private $container;

    /**
     * Der Konstruktor.
     *
     * @param callable $container the global slim container
     */
    public function __construct($container)
    {
        $this->container = $container;
    }

    /**
     * Checks, if the current user has the admin role
     */
    public function __invoke(Request $request, RequestHandler $handler)
    {
        if (!$GLOBALS['perm']->have_perm('root')) {
            throw new Error('Access Denied', 403);
        }

        return $handler->handle($request);
    }
}
