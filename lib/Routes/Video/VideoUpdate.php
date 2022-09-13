<?php

namespace Opencast\Routes\Video;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\Videos;

class VideoUpdate extends OpencastController
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
        $event = $json['event'];

        $message = [
            'type' => 'success',
            'text' => _('Das Video wurde erfolgreich aktualisiert')
        ];

        if (!$video->updateMetadata($event)) {
            $message = [
                'type' => 'error',
                'text' => _('Das Video konnte nicht bearbeitet werden')
            ];
        }


        return $this->createResponse([
            'message' => $message,
        ], $response->withStatus(200));
    }
}
