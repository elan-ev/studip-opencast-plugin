<?php

namespace Opencast\Routes\Video;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\Videos;
use Opencast\Models\VideoTags;
use Opencast\Models\Tags;
use Opencast\Models\Playlists;
use Opencast\Models\PlaylistSeminars;
use Opencast\Models\PlaylistSeminarVideos;

class VideoWorldwideShareUpdate extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $user;

        $token = $args['token'];
        $video = Videos::findByToken($token);

        if (empty($video)) {
            throw new Error(_('Das Video kann nicht gefunden werden'), 404);
        }

        if (!$video->havePerm('write')
            || !\Config::get()->OPENCAST_ALLOW_PUBLIC_SHARING
        ) {
            throw new \AccessDeniedException();
        }

        $json = $this->getRequestData($request);
        $visibility = $json['visibility'];

        if ($video->setWorldVisibility($visibility) !== true) {
            $message = [
                'type' => 'error',
                'text' => _('Beim Übertragen der Änderungen zum Videoserver ist ein Fehler aufgetreten.')
            ];
        } else {
            $message = [
                'type' => 'success',
                'text' => $visibility === 'public'
                    ? _('Das Video wurde auf weltweit zugreifbar gestellt.')
                    : _('Das Video wurde nur berechtigten Personen zugreifbar gemacht.')
            ];

        }

        return $this->createResponse([
            'message' => $message,
        ], $response->withStatus(200));
    }
}
