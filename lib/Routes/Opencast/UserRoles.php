<?php

namespace Opencast\Routes\Opencast;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\VideosUserPerms;

/**
 * This route is used by opencast itself and secured via an API token
 */
class UserRoles extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        // parse username, they are of the type lti:instid:1234567890acbdef
        $user_id = end(explode(':', $args['username']));
        $roles = [];

        if ($user_id) {
            // get all videos the user has permissions on
            foreach(VideosUserPerms::findByUser_id($user_id) as $vperm) {
                if ($vperm->perm == 'owner' || $vperm->perm == 'write') {
                    $roles[] = 'STUDIP_' . $vperm->video->episode . '_write';
                } else {
                    $roles[] = 'STUDIP_' . $vperm->video->episode . '_read';
                }
            }
        }

        return $this->createResponse([
            'username' => $args['username'],
            'roles'    => $roles
        ], $response);
    }
}
