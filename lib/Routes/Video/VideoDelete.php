<?php

namespace Opencast\Routes\Video;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\Videos;

class VideoDelete extends OpencastController
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

        // Prevent deleting livestreams.
        if ((bool) $video->is_livestream) {
            return $this->createResponse([
                'message' => [
                    'type' => 'warning',
                    'text' => _('Das Livestream-Video konnte nicht gelöscht werden')
                ],
            ], $response->withStatus(200));
        }

        $message = [
            'type' => 'success',
            'text' => _('Das Video wurde erfolgreich gelöscht')
        ];

        if ($video->getValue('trashed')) {
            // If the video is already marked as trashed, delete it permanently
            if (!$video->removeVideo()) {
                $message = [
                    'type' => 'error',
                    'text' => _('Das Video konnte nicht gelöscht werden')
                ];
            }
        }
        else {
            // Do not really delete the videos, mark them as deleted first
            $video->setValue('trashed', true);
            $video->setValue('trashed_timestamp', date('Y-m-d H:i:s'));
            $video->store();
        }

        return $this->createResponse([
            'message' => $message,
        ], $response->withStatus(200));
    }
}
