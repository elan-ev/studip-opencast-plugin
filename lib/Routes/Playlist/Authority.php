<?php

namespace Opencast\Routes\Playlist;

use Opencast\Models\Playlists;
use Opencast\Models\Videos;

class Authority
{
    public static function canAddVideoToPlaylist(\Seminar_User $user, Playlists $playlist, Videos $video)
    {
        if ($GLOBALS['perm']->have_perm('root')) {
            return true;
        }

        $perm_playlist = $playlist->getUserPerm();
        $perm_video = reset($video->perms->findBy('user_id', $user->id)->toArray());

        if (
            (empty($perm_playlist) || ($perm_playlist != 'owner' && $perm_playlist != 'write')) ||
            (empty($perm_video) || ($perm_video['perm'] != 'owner' && $perm_video['perm'] != 'write'))
        ) {
            return false;
        }

        return true;
    }
}