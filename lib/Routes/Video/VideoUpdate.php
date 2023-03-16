<?php

namespace Opencast\Routes\Video;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\Videos;
use Opencast\Models\VideoTags;
use Opencast\Models\Tags;
use Opencast\Models\Playlists;
use Opencast\Models\PlaylistSeminars;
use Opencast\Models\PlaylistSeminarVideos;

class VideoUpdate extends OpencastController
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

        $perm = $video->getUserPerm();
        if (empty($perm) ||
            ($perm != 'owner' && $perm != 'write'))
        {
            throw new \AccessDeniedException();
        }

        $json = $this->getRequestData($request);
        $event = $json['event'];

        if (isset($event['tags'])) {
            // delete all existing tags from the playlist
            VideoTags::deleteByVideo_id($video->id);

            // readd the new ones
            foreach ($event['tags'] as $new_tag) {
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

                $vltag = new VideoTags();
                $vltag->video_id = $video->id;
                $vltag->tag_id      = $tag->id;
                $vltag->store();
            }

            unset($event['tags']);
        }

        if (isset($event['cid']) && isset($event['playlist_token'])) {
            $playlist = Playlists::findOneByToken($event['playlist_token']);
            if (!empty($playlist)) {
                $playlistSeminar = PlaylistSeminars::findOneBySQL('seminar_id = ? AND playlist_id = ?', [$event['cid'], $playlist->id]);
                if (!empty($playlistSeminar)) {
                    PlaylistSeminarVideos::deleteBySQL('playlist_seminar_id = ? AND video_id = ?', [$playlistSeminar->id, $video->id]);
                    if (isset($event['seminar_visibility'])) {
                        $pvs = new PlaylistSeminarVideos();
                        $pvs->setValue('playlist_seminar_id', $playlistSeminar->id);
                        $pvs->setValue('video_id', $video->id);
                        $pvs->setValue('visibility', $event['seminar_visibility']['visibility']);
                        $pvs->setValue('visible_timestamp', $event['seminar_visibility']['visible_timestamp']);
                        $pvs->store();
                    }
                }
            }

        }

        $message = [
            'type' => 'success',
            'text' => _('Das Video wurde erfolgreich aktualisiert.')
        ];

        $update = $video->updateMetadata($event);

        if ($update !== true) {
            $message = [
                'type' => 'error',
                'text' => _('Beim übertragen der Änderungen zum Videoserver ist ein Fehler aufgetreten.')
                //    . ': '. $response['code'] . ' - '. $response['message'] // TODO this throws error
            ];
        }


        return $this->createResponse([
            'message' => $message,
        ], $response->withStatus(200));
    }
}
