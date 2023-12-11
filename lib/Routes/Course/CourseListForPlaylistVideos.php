<?php

namespace Opencast\Routes\Course;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\Playlists;
use Opencast\Models\PlaylistSeminars;

/**
 * Find the user's courses from all visible videos in a specific playlist
 */
class CourseListForPlaylistVideos extends OpencastController
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
            if ($perm->have_studip_perm($params['cid'], 'user')) {
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

        $courses_ids = PlaylistSeminars::getPlaylistVideosCourses($playlist->id, $params['cid']);

        return $this->createResponse(PlaylistSeminars::getCoursesArray($courses_ids), $response->withStatus(200));
    }
}
