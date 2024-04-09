<?php

namespace Opencast\Routes\Video;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\Playlists;
use Opencast\Models\PlaylistVideos;
use Opencast\Models\PlaylistSeminars;
use Opencast\Models\Helpers;

/**
 * Copy all playlists of a course to other courses
 */
class VideoCopyToCourse extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $perm;

        $course_id = $args['course_id'];
        if (!$perm->have_studip_perm('tutor', $course_id)) {
            throw new \AccessDeniedException();
        }

        $json = $this->getRequestData($request);
        $courses = $json['courses'];
        $playlists = [];

        try {
            // Managing playlists.
            $playlists = Playlists::findByCourse_id($course_id);
            $default_playlist_seminar = PlaylistSeminars::getDefaultPlaylistSeminar($course_id);

            if (!empty($courses) && !empty($playlists)) {
                foreach ($courses as $course) {
                    if ($perm->have_studip_perm('tutor', $course['id']) && $course['id'] !== $course_id) {
                        $has_target_default_playlist = Helpers::checkCourseDefaultPlaylist($course['id']);

                        foreach ($playlists as $playlist) {
                            // Copy source playlist to target course
                            $new_playlist = $playlist->copy();
                            if (!$new_playlist) {
                                throw new Error(_('Die Wiedergabeliste konnte nicht kopiert werden.'), 500);
                            }

                            $is_default = false;
                            if (!$has_target_default_playlist
                                && !empty($default_playlist_seminar)
                                && $default_playlist_seminar->playlist_id === $playlist->id)
                            {
                                // Set default playlist of source course if target course has no default playlist
                                $is_default = true;
                            }

                            // Link playlist copy to target course
                            PlaylistSeminars::create([
                                'playlist_id' => $new_playlist->id,
                                'seminar_id'  => $course['id'],
                                'visibility'  => 'visible',
                                'is_default'  => $is_default,
                            ]);
                        }
                    }
                }
            }

            $message = [
                'type' => 'success',
                'text' => _('Die Übertragungen wurden ausgeführt.')
            ];
        } catch (\Throwable $e) {
            $message = [
                'type' => 'error',
                'text' => _('Die Übertragungen konnten nicht abgeschlossen werden!')
            ];
        }

        return $this->createResponse([
            'message' => $message
        ], $response->withStatus(200));
    }
}
