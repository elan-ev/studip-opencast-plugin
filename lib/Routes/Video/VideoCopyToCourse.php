<?php

namespace Opencast\Routes\Video;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\VideoSeminars;
use Opencast\Models\PlaylistSeminars;

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
            if ($type === 'all') {
                $videos = VideoSeminars::findBySeminar_id($course_id);
                $playlists = PlaylistSeminars::findBySeminar_id($course_id);
            } else if ($type === 'selectedVideos' && !empty($tokens)) {
                $videos = VideoSeminars::getSeminarVideosByTokens($course_id, $tokens);
            }

            if (!empty($courses) && (!empty($videos) || !empty($playlists))) {
                foreach ($courses as $course) {
                    if ($perm->have_studip_perm('tutor', $course['id']) && $course['id'] !== $course_id) {
                        if (!empty($videos)) {
                            $target_course_vidoes = VideoSeminars::findBySeminar_id($course['id']);
                            foreach ($videos as $video) {
                                $video_id = $video->video_id;
                                $exists = array_filter($target_course_vidoes, function ($vid) use ($video_id) {
                                    return intval($vid->video_id) === intval($video_id);
                                });
                                if (!empty($exists)) {
                                    continue;
                                }
                                $video_seminar = new VideoSeminars;
                                $video_seminar->video_id = $video_id;
                                $video_seminar->seminar_id = $course['id'];
                                $video_seminar->visibility = $video->visibility ? $video->visibility : 'visible';
                                $video_seminar->store();
                            }
                        }

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
            $message_text = $type === 'all' ?
                _('Die Kurs-Verknüpfungen der Inhalte wurden ausgeführt.') :
                _('Die Kurs-Verknüpfungen des Videos wurden ausgeführt.');
            $message = [
                'type' => 'success',
                'text' => $message_text
            ];
        } catch (\Throwable $e) {
            $message_text = $type === 'all' ?
                _('Die Kurs-Verknüpfungen der Inhalte konnten nicht ausgeführt werden!') :
                _('Die Kurs-Verknüpfungen des Videos konnten nicht ausgeführt werden!');
            $message = [
                'type' => 'error',
                'text' => $message_text
            ];
        }

        return $this->createResponse([
            'message' => $message
        ], $response->withStatus(200));
    }
}
