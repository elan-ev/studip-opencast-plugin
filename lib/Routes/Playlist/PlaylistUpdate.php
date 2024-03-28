<?php

namespace Opencast\Routes\Playlist;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\Playlists;
use Opencast\Models\PlaylistTags;
use Opencast\Models\Tags;

class PlaylistUpdate extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $user;

        $playlist = Playlists::findOneByToken($args['token']);

        // check what permissions the current user has on the playlist
        $uperm = $playlist->getUserPerm();

        if (empty($uperm) || ($uperm != 'owner' && $uperm != 'write'))
        {
            throw new \AccessDeniedException();
        }

        $json = $this->getRequestData($request);

        if (isset($json['tags'])) {
            // delete all existing tags from the playlist
            PlaylistTags::deleteByPlaylist_id($playlist->id);

            // readd the new ones
            foreach ($json['tags'] as $new_tag) {
                // check if tag already exists in oc_tags

                if ($new_tag['id']) {
                    $tag = Tags::find($new_tag['id']);
                } else {
                    $tag = Tags::findOneBySQL('tag = ? AND user_id = ?', [$new_tag['tag'], $user->id]);
                }

                if (empty($tag)) {
                    $tag = new Tags();
                    $tag->tag     = $new_tag['tag'];
                    $tag->user_id = $user->id;
                    $tag->store();
                }

                $pltag = new PlaylistTags();
                $pltag->playlist_id = $playlist->id;
                $pltag->tag_id      = $tag->id;
                $pltag->store();
            }

            unset($json['tags']);
        }

        if (!$playlist->update($json)) {
            throw new Error(_('Die Wiedergabeliste konnte nicht aktualisiert werden.'), 500);
        }

        $playlist['mkdate'] = ($playlist['mkdate'] == '0000-00-00 00:00:00')
            ? 0 : \strtotime($playlist['mkdate']);

        $ret_playlist = $playlist->toSanitizedArray();

        // Add extra params to send back to frontend.
        $ret_playlist['users'] = [[
            'user_id'  => $user->id,
            'fullname' => \get_fullname($user->id),
            'perm'     => $uperm
        ]];

        if (isset($json['is_default'])) {
            $ret_playlist['is_default'] = $json['is_default'];
        }

        return $this->createResponse($ret_playlist, $response->withStatus(200));
    }
}
