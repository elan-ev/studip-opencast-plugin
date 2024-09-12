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
use Opencast\Models\Playlists;
use Opencast\Models\PlaylistsUserPerms;



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
                $roles[] = $video_share->video->episode . '_read';
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

            // Add user permission to access user-bound series and own playlists
            // $roles[] = 'STUDIP_' . $user_id;
            // TODO: does the default ROLE_USER_USERNAME catch all cases now?

            // Stud.IP-root has access to all videos and playlists
            if ($GLOBALS['perm']->have_perm('root', $user_id)) {
                $roles[] = 'ROLE_ADMIN';
            } else {
                // Handle video roles

                // get all videos the user has permissions on
                foreach (VideosUserPerms::findByUser_id($user_id) as $vperm) {
                    if (!$vperm->video->episode) continue;

                    if ($vperm->perm == 'owner' || $vperm->perm == 'write') {
                        $roles[$vperm->video->episode . '_write'] = $vperm->video->episode . '_write';
                    } else {
                        $roles[$vperm->video->episode . '_read'] = $vperm->video->episode . '_read';
                    }
                }

                // get courses with write access ('dozent', 'tutor')
                $stmt = \DBManager::get()->prepare("SELECT seminar_id FROM seminar_user
                    WHERE user_id = ? AND (status = 'dozent' OR status = 'tutor')");
                $stmt->execute([$user_id]);
                $courses_write = $stmt->fetchAll(\PDO::FETCH_COLUMN);

                // add instructor roles
                foreach ($courses_write as $course_id) {
                    $roles[$course_id . '_Instructor'] = $course_id . '_Instructor';
                }

                // Get courses with read access ('autor', 'user')
                $stmt = \DBManager::get()->prepare("SELECT seminar_id FROM seminar_user
                    WHERE user_id = ? AND (status = 'autor' OR status = 'user')");
                $stmt->execute([$user_id]);
                $courses_read = $stmt->fetchAll(\PDO::FETCH_COLUMN);

                // add learner roles
                foreach ($courses_read as $course_id) {
                    $roles[$course_id . '_Learner'] = $course_id . '_Learner';
                }

                // Handle playlist roles

                // get all playlists the user has permissions on
                foreach (PlaylistsUserPerms::findByUser_id($user_id) as $pperm) {
                    if ($pperm->perm == 'owner' || $pperm->perm == 'write') {
                        $roles[$pperm->playlist->service_playlist_id . '_write'] = 'PLAYLIST_' . $pperm->playlist->service_playlist_id . '_write';
                    } else {
                        $roles[$pperm->playlist->service_playlist_id . '_read'] = 'PLAYLIST_' . $pperm->playlist->service_playlist_id . '_read';
                    }
                }

                // find playlists with write access
                $stmt = \DBManager::get()->prepare('SELECT service_playlist_id FROM oc_playlist AS op
                    INNER JOIN oc_playlist_seminar AS ops ON (ops.playlist_id = op.id)
                    WHERE ops.seminar_id IN (:courses)'
                );
                $stmt->bindValue(':courses', $courses_write, \StudipPDO::PARAM_ARRAY);
                $stmt->execute();

                foreach ($stmt->fetchAll(\PDO::FETCH_COLUMN) as $service_playlist_id) {
                    $roles[$service_playlist_id . '_write'] = 'PLAYLIST_' . $service_playlist_id . '_write';
                }

                // find playlists with read access
                $stmt = \DBManager::get()->prepare('SELECT service_playlist_id FROM oc_playlist AS op
                    INNER JOIN oc_playlist_seminar AS ops ON (ops.playlist_id = op.id)
                    WHERE ops.seminar_id IN (:courses)
                    AND ops.visibility = "visible"'
                );
                $stmt->bindValue(':courses', $courses_read, \StudipPDO::PARAM_ARRAY);
                $stmt->execute();

                foreach ($stmt->fetchAll(\PDO::FETCH_COLUMN) as $service_playlist_id) {
                    // All seminar members have read permission on visible playlists
                    $roles[$service_playlist_id . '_read'] = 'PLAYLIST_' . $service_playlist_id . '_read';
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
