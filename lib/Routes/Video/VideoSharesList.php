<?php

namespace Opencast\Routes\Video;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\Videos;

class VideoSharesList extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {

        global $user;

        $token = $args['token'];
        $video = Videos::findByToken($token);

        if (empty($video)) {
            throw new Error(_('Das Video kann nicht gefunden werden'), 404);
        }

        $perm = $video->getUserPerm();
        // only users with owner permission are allowed to show/edit shares
        if (empty($perm) || $perm != 'owner')
        {
            throw new \AccessDeniedException();
        }

        $shares = [];
        $old_url_helper_url = \URLHelper::setBaseURL($GLOBALS['ABSOLUTE_URI_STUDIP']);
        foreach ($video->shares->toArray() as $share) {
            $share['link'] = \URLHelper::getURL(
                "plugins.php/opencast/redirect/perform/share/{$share['token']}",
                ['cancel_login' => 1]
            );
            $shares[] = $share;
        }
        \URLHelper::setBaseURL($old_url_helper_url);

        return $this->createResponse([
            'perms'  => $video->perms->toSanitizedArray(),
            'shares' =>  $shares
        ], $response->withStatus(200));
    }
}
