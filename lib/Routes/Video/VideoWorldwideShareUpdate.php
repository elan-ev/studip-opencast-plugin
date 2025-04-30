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

        // Take care of running videos by returning a proper response not throwing an exception!
        if ($video->state === 'running') {
            return $this->createResponse(
                _('Das Video befindet sich aktuell in der Bearbeitung. Bitte versuchen Sie es später erneut.'),
                $response->withStatus(409)
            );
        }

        if (!$video->havePerm('write')
            || !\Config::get()->OPENCAST_ALLOW_PUBLIC_SHARING
        ) {
            throw new \AccessDeniedException();
        }

        $json = $this->getRequestData($request);
        $visibility = $json['visibility'];

        $response_code = 500;
        $response_message = _('Beim Übertragen der Änderungen zum Videoserver ist ein Fehler aufgetreten.');

        try {
            $result = $video->setWorldVisibility($visibility);
            if ($result === true) {
                $response_code = 200;
                $response_message = $visibility === 'public'
                    ? _('Das Video wurde auf weltweit zugreifbar gestellt.')
                    : _('Das Video wurde nur berechtigten Personen zugreifbar gemacht.');
            }
        } catch (\Throwable $th) {
            $response_message .= ' (' . $th->getMessage() . ')';
        }

        return $this->createResponse($response_message, $response->withStatus($response_code));
    }
}
