<?php

namespace Opencast\Routes\Video;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\Videos;
use Opencast\Models\VideosArchive;

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

        $message = [
            'type' => 'success',
            'text' => _('Das Video wurde erfolgreich gelöscht')
        ];



        // do not really delete the videos, put them into an archive first
        $archive = VideosArchive::findOneByEpisode($video->episode);

        if (empty($archive)) {
            $archive = new VideosArchive();
            $archive->setData($video->toArray());
            $archive->store();
        }

        // only delete the db-entry
        $video->delete();

        /*
        if (!$video->removeVideo()) {
            $message = [
                'type' => 'error',
                'text' => _('Das Video kann nicht gelöscht werden')
            ];
        }
        */

        return $this->createResponse([
            'message' => $message,
        ], $response->withStatus(200));
    }
}
