<?php

namespace Opencast\Routes\Opencast;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\VideosUserPerms;
use Opencast\Models\Videos;
use Opencast\Models\Filter;
use Opencast\Models\VideosShares;



/**
 * This route is used by opencast itself and secured via an API token
 */
class UserRoles extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        // parse username, they are of the type lti:instid:1234567890acbdef

        $user_id = null;
        $share_uuid = null;

        if (strpos($args['username'], 'share:') !== false) {
            $username_args_parts = explode(':', $args['username']);
            $share_uuid = end($username_args_parts);
        } else  if (strpos($args['username'], 'lti:') === 0) {
            $username_args_parts = explode(':', $args['username']);
            $user_id = end($username_args_parts);
        } else {
            $user_id = get_userid($args['username']);
        }

        $roles = [];

        if (!empty($share_uuid)) {
            $video_share = VideosShares::findByUuid($share_uuid);
            if (!empty($video_share)) {
                $roles[] = 'STUDIP_' . $video_share->video->episode . '_read';
            }
        } else if (!empty($user_id)) {
            // Stud.IP-root has access to all videos
            if ($GLOBALS['perm']->have_perm('root', $user_id)) {
                foreach(Videos::findBySQL('episode IS NOT NULL') as $video) {
                    $roles[] = 'STUDIP_' . $video->episode . '_write';
                }
            } else {
                // get all videos the user has permissions on
                foreach(VideosUserPerms::findByUser_id($user_id) as $vperm) {
                    if (!$vperm->video->episode) continue;

                    if ($vperm->perm == 'owner' || $vperm->perm == 'write') {
                        $roles[$vperm->video->episode . '_write'] = 'STUDIP_' . $vperm->video->episode . '_write';
                    } else {
                        $roles[$vperm->video->episode . '_read'] = 'STUDIP_' . $vperm->video->episode . '_read';
                    }
                }

                // find videos with direct access
                foreach (Videos::getUserVideos(new Filter([
                    'limit'  => -1
                ])) as $video) {
                    if (!$vperm->video->episode) continue;

                    $roles[$vperm->video->episode . '_read'] = 'STUDIP_' . $vperm->video->episode . '_read';
                }


                // first, get all courses user has access to
                $stmt = \DBManager::get()->prepare("SELECT seminar_id FROM seminar_user
                    WHERE user_id = ?");

                $stmt->execute([$user_id]);
                $courses = $stmt->fetchAll(\PDO::FETCH_COLUMN);

                // find videos in seminars
                $stmt = \DBManager::get()->prepare("SELECT episode FROM oc_video_seminar
                    INNER JOIN oc_video ON (oc_video.id = video_id)
                    WHERE seminar_id IN (:courses)");
                $stmt->bindValue(':courses', $courses, \StudipPDO::PARAM_ARRAY);
                $stmt->execute();

                foreach ($stmt->fetchAll(\PDO::FETCH_COLUMN) as $episode) {
                    $roles[$episode . '_read'] = 'STUDIP_' . $episode . '_read';
                }


                // find videos in accessible playlists
                $stmt = \DBManager::get()->prepare("SELECT episode FROM oc_playlist_seminar
                    INNER JOIN oc_playlist_video USING (playlist_id)
                    INNER JOIN oc_video ON (oc_video.id = oc_playlist_video.video_id)
                    WHERE seminar_id IN (:courses)");
                $stmt->bindValue(':courses', $courses, \StudipPDO::PARAM_ARRAY);
                $stmt->execute();

                foreach ($stmt->fetchAll(\PDO::FETCH_COLUMN) as $episode) {
                    $roles[$episode . '_read'] = 'STUDIP_' . $episode . '_read';
                }
            }
        }

        return $this->createResponse([
            'username' => $args['username'],
            'roles'    => array_values($roles)
        ], $response);
    }
}
