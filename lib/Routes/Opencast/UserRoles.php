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

        $user_id    = null;
        $share_uuid = null;
        $email      = null;
        $fullname   = null;

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
            } else {
                throw new Error('Share not found', 404);
            }
        } else if (!empty($user_id)) {
            // check, if the user exists
            $user = \User::find($user_id);

            if (empty($user)) {
                throw new Error('User not found', 404);
            }

            $email    = $user->email;
            $fullname = $user->getFullName();

            // Add user permission to access user-bound series
            $roles[] = 'STUDIP_' . $user_id;

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

                // find videos in accessible playlists
                $stmt = \DBManager::get()->prepare('SELECT episode FROM oc_playlist_seminar AS ops
                    INNER JOIN oc_playlist_video         AS opv  USING (playlist_id)
                    INNER JOIN oc_video                  AS ov   ON    (ov.id = opv.video_id)
                    LEFT  JOIN oc_playlist_seminar_video AS opsv ON    (opsv.playlist_seminar_id = ops.id AND opsv.video_id = ov.id)
                    WHERE ops.seminar_id IN (:courses)
                    AND (opsv.visibility IS NULL AND opsv.visible_timestamp IS NULL AND ops.visibility = "visible"
                        OR opsv.visibility = "visible" AND opsv.visible_timestamp IS NULL
                        OR opsv.visible_timestamp < NOW() + INTERVAL 15 MINUTE)'
                );
                $stmt->bindValue(':courses', $courses, \StudipPDO::PARAM_ARRAY);
                $stmt->execute();

                foreach ($stmt->fetchAll(\PDO::FETCH_COLUMN) as $episode) {
                    $roles[$episode . '_read'] = 'STUDIP_' . $episode . '_read';
                }
            }
        } else {
            throw new Error('User not found', 404);
        }

        return $this->createResponse([
            'username' => $args['username'],
            'email'    => $email ?: null,
            'fullname' => $fullname ?: null,
            'roles'    => array_values($roles)
        ], $response);
    }
}
