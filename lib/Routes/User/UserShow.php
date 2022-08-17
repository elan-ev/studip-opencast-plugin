<?php

namespace Opencast\Routes\User;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;

class UserShow extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $user, $perm;

        $data = [
            'id'       => $user->id,
            'username' => $user->username,
            'fullname' => get_fullname($user->id),
            'status'   => $user->perms,
            'admin'    => \RolePersistence::isAssignedRole(
                $GLOBALS['user']->user_id,
                $this->container['roles']['admin']),
            'can_edit' => $perm->have_perm('tutor')
        ];

        return $this->createResponse([
            'type' => 'user',
            'id'   => $user->id,
            'data' => $data
        ], $response);
    }
}
