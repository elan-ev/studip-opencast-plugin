<?php

namespace Opencast\Routes\Video;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\Videos;

class VideoAdd extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        $json = $this->getRequestData($request);
        $event = $json['event'];

        $episode_id = $args['episode_id'];

        $ret = [];

        $video = Videos::findByEpisode($episode_id);
        if (!empty($video) || !isset($event['config_id'])) {
            $message = [
                'type' => 'error',
                'text' => _('Beim Erstellen des Videos ist ein Fehler aufgetreten.')
            ];
        }
        else {
            $video = new Videos;
            $video->setData([
                'episode'     => $episode_id,
                'config_id'   => $event['config_id'],
                'title'       => $event['title'],
                'description' => $event['description'],
                'duration'    => $event['duration'],
                'state'       => $event['state'],
                'available'   => false
            ]);
            if (!$video->token) {
                $video->token = bin2hex(random_bytes(8));
            }
            $video->store();

            $ret = $video->toSanitizedArray();

            $message = [
                'type' => 'success',
                'text' => _('Das Video wurde erfolgreich erstellt.')
            ];
        }

        return $this->createResponse([
            'message' => $message,
            'event'   => $ret
        ], $response->withStatus(200));
    }
}
