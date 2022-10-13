<?php

namespace Opencast\Routes\User;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;

class UserList extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $user, $perm;

        $sql = \GlobalSearchUsers::getSQL($args['search_term'], [], 100);

        $users = \DBManager::get()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        return $this->createResponse([
            'users' => $users
        ], $response);
    }
}
