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
use Opencast\Models\PlaylistSeminarVideos;

class PlaylistVideoList extends OpencastController
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

            if (empty($uperm) || !$uperm['perm'])
            {
                throw new \AccessDeniedException();
            }
        }

        // show videos for this playlist and filter them with optional additional filters
        $videos = Videos::getPlaylistVideos($playlist->id, new Filter($params));

        $ret = [];
        foreach ($videos['videos'] as $video) {
            $ret_video = $video->toSanitizedArray();
            $psv = PlaylistSeminarVideos::findOneBySQL(
                "LEFT JOIN oc_playlist_seminar AS ops ON ops.id = playlist_seminar_id
                WHERE video_id = ?
                AND playlist_id = ?
                AND seminar_id = ?", [$video->id, $playlist->id, $params['cid']]);
            if (!empty($psv)) {
                $ret_video['playlist_seminar'] = [];
                $ret_video['playlist_seminar']['visibility'] = $psv->getValue('visibility');
                $ret_video['playlist_seminar']['visible_timestamp'] = $psv->getValue('visible_timestamp');
            }
            $ret[] = $ret_video;
        }

        return $this->createResponse([
            'videos' => $ret,
            'count'  => $videos['count'],
            'sql'    => $videos['sql']
        ], $response);
    }
}
