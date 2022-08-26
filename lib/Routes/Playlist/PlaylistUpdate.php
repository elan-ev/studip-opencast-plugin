<?php

namespace Opencast\Routes\Playlist;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\Playlists;
use Opencast\Models\PlaylistsUserPerms;
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
        $perm = reset($playlist->perms->findBy('user_id', $user->id)->toArray());

        if (empty($perm) || (
            $perm['perm'] != 'owner' && $perm['perm'] != 'write'))
        {
            throw new \AccessDeniedException();
        }

        $json = $this->getRequestData($request);

        // delete all existing tags from the playlist
        PlaylistTags::deleteByPlaylist_id($playlist->id);

        // readd the new ones
        foreach ($json['tags'] as $name) {
            // check if tag already exists in oc_tags
            $tag = Tags::findOneByTag($name);

            if (empty($tag)) {
                $tag = new Tags();
                $tag->tag = $name;
                $tag->store();
            }

            $pltag = new PlaylistTags();
            $pltag->playlist_id = $playlist->id;
            $pltag->tag_id      = $tag->id;
            $pltag->store();
        }

        unset($json['tags']);

        $playlist->setData($json);
        $playlist->store();

        $ret_playlist = $playlist->toSanitizedArray();
        $ret_playlist['users'] = [[
            'user_id'  => $perm['user_id'],
            'fullname' => \get_fullname($perm['user_id']),
            'perm'     => $perm['perm']
        ]];

        return $this->createResponse($ret_playlist, $response->withStatus(200));
    }
}
