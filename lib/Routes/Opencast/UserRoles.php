<?php

namespace Opencast\Routes\Opencast;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\VideosUserPerms;
use Opencast\Models\VideosShares;
use Opencast\Models\PlaylistsUserPerms;
use Opencast\Models\Config;
use Opencast\Helpers\PlaylistMigration;



/**
 * This route is used by opencast itself and secured via an API token
 */
class UserRoles extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        // parse username, they are of the type lti:instid:1234567890acbdef
        $plugin_id = $this->container->get('plugin')->getPluginId();

        // Check whether all opencast configs support episode ID role access.
        $episode_id_role_access = true;
        foreach (Config::findBySQL('1') as $config) {
            $episode_id_role_access &= $config->settings['episode_id_role_access'] ?? false;
        }

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
                $roles[] = 'ROLE_EPISODE_' . $video_share->video->episode . '_READ';
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

            // Stud.IP-root has access to all videos and playlists
            if ($GLOBALS['perm']->have_perm('root', $user_id)) {
                $roles[] = 'ROLE_ADMIN';
            }

            // Admin users have permissions on videos of all administrated courses
            else if ($GLOBALS['perm']->have_perm('admin', $user_id)) {

                $stmt = \DBManager::get()->prepare("SELECT seminar_id FROM :table as s
                    JOIN Institute USING(institut_id)
                    JOIN user_inst ON user_inst.institut_id IN (fakultaets_id, s.institut_id)
                    JOIN tools_activated ON seminar_id = range_id AND range_type = 'course'
                    WHERE user_id = :user_id AND plugin_id = :plugin_id GROUP BY seminar_id");

                if (\Config::get()->ALLOW_ADMIN_RELATED_INST) {
                    $stmt->bindValue(':table', 'seminar_inst', \StudipPDO::PARAM_COLUMN);
                } else {
                    $stmt->bindValue(':table', 'seminare', \StudipPDO::PARAM_COLUMN);
                }

                $stmt->execute([':user_id' => $user_id, ':plugin_id' => $plugin_id]);
                $courses = $stmt->fetchAll(\PDO::FETCH_COLUMN);

                foreach ($courses as $course_id) {
                    $roles[$course_id . '_Instructor'] = $course_id . '_Instructor';
                }

            } else {
                // Handle video roles

                // get all videos of courseware blocks in courses and add them to the permission list as well, ignore if user has permission through a course role
                $stmt_courseware = \DBManager::get()->prepare("SELECT episode FROM cw_blocks
                    JOIN cw_containers ON (cw_containers.id = cw_blocks.container_id)
                    JOIN cw_structural_elements ON (cw_structural_elements.id = cw_containers.structural_element_id AND range_type = 'course')
                    JOIN seminar_user ON (seminar_id = cw_structural_elements.range_id)
                    JOIN oc_video ON (oc_video.token = CONVERT(JSON_VALUE(cw_blocks.payload, '$.token') USING latin1))
                    WHERE cw_blocks.block_type = 'plugin-opencast-video' AND seminar_user.user_id = :user_id
                    AND NOT EXISTS (
                        SELECT 1
                        FROM oc_playlist_video
                        JOIN oc_playlist_seminar USING (playlist_id)
                        WHERE video_id = oc_video.id
                        AND seminar_user.seminar_id = oc_playlist_seminar.seminar_id
                    )
                ");

                $stmt_courseware->execute([':user_id' => $user_id]);

                while($episode = $stmt_courseware->fetchColumn()) {
                    if ($episode_id_role_access) {
                        $roles['ROLE_EPISODE_' . $episode . '_READ'] = 'ROLE_EPISODE_' . $episode . '_READ';
                    } else {
                        $roles[$episode . '_read'] = $episode . '_read';
                    }
                }

                $stmt_courses = \DBManager::get()->prepare("SELECT seminar_id FROM seminar_user
                    JOIN tools_activated ON seminar_id = range_id AND range_type = 'course'
                    AND plugin_id = :plugin_id
                    WHERE user_id = :user_id AND status IN (:status)");

                // configure which global role has access to courses
                $course_write_perms = $course_read_perms = [];

                if (\Config::get()->OPENCAST_TUTOR_EPISODE_PERM) {
                    $course_write_perms = ['dozent', 'tutor'];
                    $course_read_perms  = ['autor', 'user'];
                } else {
                    $course_write_perms = ['dozent'];
                    $course_read_perms  = ['tutor', 'autor', 'user'];
                }

                // get courses with write access
                $stmt_courses->bindValue(':status', $course_write_perms, \StudipPDO::PARAM_ARRAY);
                $stmt_courses->execute([':user_id' => $user_id, ':plugin_id' => $plugin_id]);
                $courses_write = $stmt_courses->fetchAll(\PDO::FETCH_COLUMN);

                // Handle deputies ("Dozentenvertretung") as well
                $courses_write = array_merge(
                    $courses_write,
                    array_column(\Deputy::findDeputyCourses($user_id)->toArray(), 'range_id')
                );

                // add instructor roles
                foreach ($courses_write as $course_id) {
                    $roles[$course_id . '_Instructor'] = $course_id . '_Instructor';
                }

                // Get courses with read access
                $stmt_courses->bindValue(':status', $course_read_perms, \StudipPDO::PARAM_ARRAY);
                $stmt_courses->execute([':user_id' => $user_id, ':plugin_id' => $plugin_id]);
                $courses_read = $stmt_courses->fetchAll(\PDO::FETCH_COLUMN);

                // add learner roles
                foreach ($courses_read as $course_id) {
                    $roles[$course_id . '_Learner'] = $course_id . '_Learner';
                }

                // Get all videos permissions of the user except those for videos to which the user has already write
                // permissions through course memberships and course playlists,  in order to reduce the number of roles.
                $vperms = VideosUserPerms::findBySQL("
                    user_id = :user_id
                    AND NOT EXISTS (
                        SELECT 1
                        FROM oc_playlist_video
                        JOIN oc_playlist_seminar USING (playlist_id)
                        WHERE video_id = oc_video_user_perms.video_id AND seminar_id IN ( :course_ids )
                    )
                ", [':user_id' => $user_id, ':course_ids' => $courses_write]);

                foreach ($vperms as $vperm) {
                    if (!$vperm->video->episode) continue;

                    if ($vperm->perm == 'owner' || $vperm->perm == 'write') {
                        if ($episode_id_role_access) {
                            $roles['ROLE_EPISODE_' . $vperm->video->episode . '_READ'] = 'ROLE_EPISODE_' . $vperm->video->episode . '_READ';
                            $roles['ROLE_EPISODE_' . $vperm->video->episode . '_WRITE'] = 'ROLE_EPISODE_' . $vperm->video->episode . '_WRITE';
                        } else {
                            $roles[$vperm->video->episode . '_write'] = $vperm->video->episode . '_write';
                        }
                    } else {
                        if ($episode_id_role_access) {
                            $roles['ROLE_EPISODE_' . $vperm->video->episode . '_READ'] = 'ROLE_EPISODE_' . $vperm->video->episode . '_READ';
                        } else {
                            $roles[$vperm->video->episode . '_read'] = $vperm->video->episode . '_read';
                        }
                    }
                }

                // Handle playlist roles

                if (PlaylistMigration::isConverted()) {
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
