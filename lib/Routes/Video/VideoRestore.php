<?php

namespace Opencast\Routes\Video;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\Videos;

class VideoRestore extends OpencastController
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

        // Restore the Video
        $video->setValue('trashed', false);
        $video->setValue('trashed_timestamp', '0000-00-00 00:00:00');
        $video->store();

        $message = [
            'type' => 'success',
            'text' => _('Das Video wurde erfolgreich wiederhergestellt')
        ];

        return $this->createResponse([
            'message' => $message,
        ], $response->withStatus(200));
    }
}
