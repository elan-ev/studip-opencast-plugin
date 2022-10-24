<?php

namespace Opencast\Routes\Playlist;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\Filter;
use Opencast\Models\Playlists;
use Opencast\Models\Videos;

class PlaylistVideoList extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        $params = $request->getQueryParams();

        // first, check if user has access to this playlist
        $playlist = Playlists::findOneByToken($args['token']);

        if (!$playlist) {
            throw new \AccessDeniedException();
        }

        // check if playlist is connected to the passed course and user is part of that course as well
        $permission = false;
        if ($args['cid']) {
            if (sizeof(\CourseMember::findByCourse($args['cid']))) {
                $permission = true;
            }
        }

        if (!$args['cid'] || !$permission) {
            // check what permissions the current user has on the playlist
            $perm = $playlist->getUserPerm();

            if (empty($perm) || !$perm['perm'])
            {
                throw new \AccessDeniedException();
            }
        }

        // show videos for this playlist and filter them with optional additional filters
        $videos = Videos::getPlaylistVideos($playlist->id, new Filter($params));

        $ret = [];
        foreach ($videos['videos'] as $video) {
            $ret[] = $video->toSanitizedArray();
        }

        return $this->createResponse([
            'videos' => $ret,
            'count'  => $videos['count'],
            'sql'    => $videos['sql']
        ], $response);
    }
}
