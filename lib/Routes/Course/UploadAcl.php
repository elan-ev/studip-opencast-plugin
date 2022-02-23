<?php

namespace Opencast\Routes\Course;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\LTI\ACL;
use Opencast\Providers\Perm;

class UploadAcl extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $perm;

        if (Perm::editAllowed($course_id)) {
            $oc_acl = ACL::getUploadXML($course_id);
            return $this->createResponse(['oc_acl' => $oc_acl], $response);
        } else {
            throw new AccessDeniedException();
        }
    }
}
