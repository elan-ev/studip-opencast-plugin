<?php

namespace Opencast\Routes\Playlist;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\Playlists;
use Opencast\Models\PlaylistsUserPerms;
use Opencast\Models\Helpers;

/**
 * Find the playlists for the passed user
 */
class PlaylistList extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $user, $perm;

        // find all playlists, the current user has access to
        $playlists = Playlists::findByUser_id($user->id);

        foreach ($playlists as $playlist) {
            // check what permissions the current user has on the playlist
            foreach($playlist->perms as $uperm) {
                if ($uperm->perm == 'owner' || $uperm->perm == 'write' || $uperm->perm == 'read') {
                    // Add playlist, if the user has access
                    $playlist['mkdate'] = ($playlist['mkdate'] == '0000-00-00 00:00:00')
                    ? 0 : \strtotime($playlist['mkdate']);
                    $playlist_list[$playlist->id] = $playlist->toSanitizedArray();
                }
            }
        }

        // find playlists in courses user has 'dozent' access to (root and admin are omitted)
        /*
        if (!$perm->have_perm('admin')) {
            // get my courses
            $courses = Helpers::getMyCourses($user->id);

            // find playlists in these courses
            $stmt = \DBManager::get()->prepare("SELECT DISTINCT playlist_id FROM oc_playlist_seminar AS ops
                JOIN seminar_user AS su ON (
                    su.seminar_id = ops.seminar_id
                    AND su.status = 'dozent'
                    AND su.user_id = :user_id
                )
                WHERE ops.seminar_id IN (:courses)");
            $stmt->bindValue(':courses', $courses, \StudipPDO::PARAM_ARRAY);
            $stmt->execute([
                ':user_id' => $user->id
            ]);

            while ($playlist_id = $stmt->fetchColumn()) {
                $playlist = Playlists::find($playlist_id);

                $playlist['mkdate'] = ($playlist['mkdate'] == '0000-00-00 00:00:00')
                    ? 0 : \strtotime($playlist['mkdate']);
                $playlist_list[$playlist->id] = $playlist->toSanitizedArray();
            }
        }
        */

        return $this->createResponse(array_values($playlist_list) ?: [], $response);
    }
}
