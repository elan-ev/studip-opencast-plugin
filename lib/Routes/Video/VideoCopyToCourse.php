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
        $tokens = $json['tokens'];
        $type = $json['type'];
        $playlists = [];

        $videos = [];
        try {
            // Managing playlists.
            if (in_array($type, ['all', 'playlists'])) {
                $playlists = PlaylistSeminars::findBySeminar_id($course_id);
            }

            if (!empty($courses) && (!empty($videos) || !empty($playlists))) {
                foreach ($courses as $course) {
                    if ($perm->have_studip_perm('tutor', $course['id']) && $course['id'] !== $course_id) {
                        if (!empty($playlists)) {
                            $target_course_playlists = PlaylistSeminars::findBySeminar_id($course['id']);
                            foreach ($playlists as $playlist) {
                                $playlist_id = $playlist->playlist_id;
                                $exists = array_filter($target_course_playlists, function ($pl) use ($playlist_id) {
                                    return intval($pl->playlist_id) === intval($playlist_id);
                                });
                                if (!empty($exists)) {
                                    continue;
                                }

                                // if this is the courses default playlist, copy the videos to the new courses default playlist
                                if ($playlist->is_default) {
                                    $default_playlist = Helpers::checkCoursePlaylist($course['id']);
                                    $stmt = DBManager::get()->prepare("INSERT INTO oc_playlist_video (playlist_id, video, order)
                                        SELECT :target, video, order, FROM oc_playlist_video
                                             WHERE playlist_id = :source");
                                    $stmt->execute([
                                        ':target' => $default_playlist->id,
                                        ':source' => $playlist->id
                                    ]);
                                } else {
                                    $playlist_seminar = new PlaylistSeminars;
                                    $playlist_seminar->playlist_id = $playlist->playlist_id;
                                    $playlist_seminar->seminar_id = $course['id'];
                                    $playlist_seminar->visibility = 'visible';

                                    $playlist_seminar->store();
                                }


                            }
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
