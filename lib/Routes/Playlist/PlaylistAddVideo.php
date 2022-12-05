<?php

namespace Opencast\Routes\Playlist;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\Playlists;
use Opencast\Models\Videos;
use Opencast\Models\PlaylistVideos;

class PlaylistAddVideo extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $user, $perm;

        $playlist = Playlists::findOneByToken($args['token']);
        $video = Videos::findOneByToken($args['vid_token']);

        // check what permissions the current user has on the playlist and video
        $perm_playlist = $playlist->getUserPerm();
        $perm_video = reset($video->perms->findBy('user_id', $user->id)->toArray());

        if (!$perm->have_perm('root') && (
            (empty($perm_playlist) || ($perm_playlist != 'owner' && $perm_playlist != 'write')) ||
            (empty($perm_video) || ($perm_video['perm'] != 'owner' && $perm_video['perm'] != 'write'))
        )) {
            throw new \AccessDeniedException();
        }

        $playlist_video = PlaylistVideos::findOneBySql('playlist_id = ? AND video_id = ?', [
            $playlist->id, $video->id
        ]);

        if (empty($playlist_video)) {
            $playlist_video = new PlaylistVideos;
            $playlist_video->setData([
                'playlist_id' => $playlist->id,
                'video_id' => $video->id,
                'order' => 0 //TODO set order correctly
            ]);
            $playlist_video->store();
        }

        return $response->withStatus(204);
    }
}