<?php

namespace Opencast\Routes\Playlist;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Models\Playlists;
use Opencast\OpencastController;
use Opencast\OpencastTrait;

class PlaylistUpdateEntries extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        /** @var Playlists $playlist */
        $playlist = Playlists::findOneByToken($args['token']);

        // check what permissions the current user has on the playlist
        $uperm = $playlist->getUserPerm();

        if (empty($uperm) || ($uperm != 'owner' && $uperm != 'write'))
        {
            throw new \AccessDeniedException();
        }

        $json = $this->getRequestData($request);

        // Opencast playlist entries
        // TODO: Should we load the oc playlist in Backend instead in Frontend?
        $entries = $json['entries'];

        // Update playlist entries
        $playlist->setEntries(json_decode(json_encode($entries)));  // Convert assoc array to object

        return $response->withStatus(200);
    }
}
