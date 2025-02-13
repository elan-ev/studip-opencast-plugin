<?php

namespace Opencast\Routes\Video;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\Videos;

class VideoReport extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        if (!\Config::get()->OPENCAST_ALLOW_TECHNICAL_FEEDBACK) {
            throw new \AccessDeniedException();
        }

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
        $description = $json['description'];

        $message = [
            'type' => 'error',
            'text' => _('Feedback kann nicht gesendet werden')
        ];

        if (!empty($description) && $video->reportVideo($description)) {
            $message = [
                'type' => 'success',
                'text' => _('Das Feedback wurde erfolgreich gesendet')
            ];
        }

        return $this->createResponse([
            'message' => $message,
        ], $response->withStatus(200));
    }
}
