<?php

namespace Opencast\Routes\Playlist;

use Opencast\Models\Playlists;
use Opencast\Models\Videos;
use Opencast\Providers\Perm;

class Authority
{
    public static function canAddAndRemoveVideoInPlaylist(\Seminar_User $user, Playlists $playlist, Videos $video, $course_id = null)
    {
        if ($GLOBALS['perm']->have_perm('root')) {
            return true;
        }

        $required_course_perm = \Config::get()->OPENCAST_TUTOR_EPISODE_PERM ? 'tutor' : 'dozent';
        if ($playlist->haveCoursePerm($required_course_perm) && $video->haveCoursePerm($required_course_perm)) {
            return true;
        }

        $perm_playlist = $playlist->getUserPerm();
        $perm_video = reset($video->perms->findBy('user_id', $user->id)->toArray());

        // Checking if the uploaded video and the request to add to playlist is a student upload.
        if (!empty($course_id) && self::isStudentUpload($course_id, $perm_video)) {
            return true;
        }

        if (
            (empty($perm_playlist) || ($perm_playlist != 'owner' && $perm_playlist != 'write')) ||
            (empty($perm_video) || ($perm_video['perm'] != 'owner' && $perm_video['perm'] != 'write'))
        ) {
            return false;
        }

        return true;
    }

    /**
     * Checks whether the the video comes from the a student and the course student upload is allowed.
     * @param string $course_id Course ID
     * @param array $perm_video Video permissions array
     *
     * @return bool whether it could be a student upload and it is allowed in the course
     */
    public static function isStudentUpload($course_id, $perm_video)
    {
        $is_upload_allowed = Perm::uploadAllowed($course_id);
        return $is_upload_allowed && $perm_video['perm'] == 'owner';
    }
}
