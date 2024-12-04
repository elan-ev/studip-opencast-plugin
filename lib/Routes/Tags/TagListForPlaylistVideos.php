<?php

namespace Opencast\Routes\Tags;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\Playlists;
use Opencast\Models\Tags;

class TagListForPlaylistVideos extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $perm;

        $params = $request->getQueryParams();

        // first, check if user has access to this playlist
        $playlist = Playlists::findOneByToken($args['token']);
        if (!$playlist) {
            throw new \AccessDeniedException();
        }

        // check if playlist is connected to the passed course and user is part of that course as well
        $permission = false;
        if ($params['cid']) {
            if ($perm->have_studip_perm('user', $params['cid'])) {
                $permission = true;
            }
        }

        if (!$params['cid'] || !$permission) {
            // check what permissions the current user has on the playlist
            $uperm = $playlist->getUserPerm();

            if (empty($uperm) || !$uperm)
            {
                throw new \AccessDeniedException();
            }
        }

        $ret = Tags::getPlaylistVideosTags($playlist->id, $params['cid']);

        return $this->createResponse($ret, $response->withStatus(200));
    }
}
