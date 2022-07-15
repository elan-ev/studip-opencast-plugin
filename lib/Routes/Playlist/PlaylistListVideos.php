<?php

namespace Opencast\Routes\Playlist;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\Videos;

class PlaylistListVideos extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $user;

        // find all videos of the playlist, the current user has access to
        $playlist_token = $args['token'];
        $videos = Videos::findByPlaylist_token($playlist_token);

        foreach ($videos as $video) {
            // check what permissions the current user has on the playlist
            foreach ($video->perms as $perm) {
                if ($perm->perm == 'owner' || $perm->perm == 'write' || $perm->perm == 'read') {
                    // Add playlist, if the user has access
                    $video_list[] = $video->toSanitizedArray();
                }
            }
        }

        //return $this->createResponse($video_list, $response);

        $test = [
            'id'            => '24',
            'token'	        => 'fedcba1234',
            'config_id'	    => '22',
            'autor'         => 'Frank Mirau',          // Get it from oc_video_user_perms
            'contributors'  => 'Rüdiger Hell',      // Where to get that from?
            'episode'	    => 'abc-def-ghi-123-456',
            'title'	        => 'Testtitel in playlist',
            'description'	=> 'Beschreibung in playlist',
            'duration'	    => '1230000',
            'views'	        => '3',
            'preview'	    => 'https://studip.me',
            'publication'	=> 'https://studip.me',
            'visibility'	=> 'public',
            'chdate'	    => strtotime('2022-06-07 14:23:51'),
            'mkdate'        => strtotime('2022-06-05 09:12:12')
        ];

        $test2 = $test;
        $test2['id'] = '13';
        $test2['token'] = 'akioxc1234';

        $test3 = $test;
        $test3['id'] = '16';
        $test3['token'] = 'opjcv1234';

        return $this->createResponse([
            'videos' => [$test, $test2, $test3],//$ret,
            'count'  => 2//$count
        ], $response);
    }
}