<?php

namespace Opencast\Routes\Video;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\Videos;
use Opencast\Models\VideoSeminars;

class VideoAddToCourse extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        $token = $args['token'];
        $video = Videos::findByToken($token);

        if (empty($video)) {
            throw new Error(_('Das Video kann nicht gefunden werden'), 404);
        }

        $perm = $video->getUserPerm();
        if (empty($perm) || 
            ($perm != 'owner' && $perm != 'write'))
        {
            throw new \AccessDeniedException();
        }

        $json = $this->getRequestData($request);
        $courses = $json['courses'];

        try {
            // Clear all existing record for this video.
            VideoSeminars::deleteBySql(
                'video_id = ?', [$video->id]
            );

            // Add record to the VideoSeminars based on courses
            if (!empty($courses)) {
                foreach ($courses as $course) {
                    $video_seminar = new VideoSeminars;
                    $video_seminar->video_id = $video->id;
                    $video_seminar->seminar_id = $course['id'];
                    $video_seminar->visibility = $video->visibility ? $video->visibility : 'visible';
                    $video_seminar->store();
                }
            }

            $message = [
                'type' => 'success',
                'text' => _('Der Kurs-Verknüpfung des Videos wurde aktualisiert.')
            ];
        } catch (\Throwable $th) {
            $message = [
                'type' => 'error',
                'text' => _('Der Kurs-Verknüpfung des Videos konnte nicht aktualisiert werden')
            ];
        }

        return $this->createResponse([
            'message' => $message,
        ], $response->withStatus(200));
    }
}
