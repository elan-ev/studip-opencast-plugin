<?php

namespace Opencast\Routes\Course;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\Playlists;
use Opencast\Models\PlaylistVideos;

class CourseRemovePlaylist extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $user;

        $playlist = Playlists::findOneByToken($args['token']);
        $course_id = $args['course_id'];

        // check what permissions the current user has on the playlist and video
        $perm_playlist = reset($playlist->perms->findBy('user_id', $user->id)->toArray());

        if (empty($perm_playlist) || ($perm_playlist['perm'] != 'owner' && $perm_playlist['perm'] != 'write'))
        {
            throw new \AccessDeniedException();
        }

        PlaylistVideos::deleteBySQL('playlist_id = ?', [
            $playlist->id
        ]);

        return $response->withStatus(204);
    }
}