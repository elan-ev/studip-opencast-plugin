<?php

namespace Opencast\Routes\Video;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\Videos;
use Opencast\Models\VideosUserPerms;
use Opencast\Models\VideosShares;

class VideoSharesUpdate extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {

        global $user;

        $sharing = \Config::get()->OPENCAST_ALLOW_SHARING ? true : false;
        $perm_assignment = \Config::get()->OPENCAST_ALLOW_PERMISSION_ASSIGNMENT ? true : false;

        $token = $args['token'];
        $video = Videos::findByToken($token);

        if (empty($video)) {
            throw new Error(_('Das Video kann nicht gefunden werden'), 404);
        }

        $json = $this->getRequestData($request);

        // only users with write permission are allowed to show/edit shares
        if (!$video->havePerm('write'))
        {
            throw new \AccessDeniedException();
        }

        /* * * * * * * * * * * * * * * * * * * *
         *   U S E R   P E R M I S S I O N S   *
         * * * * * * * * * * * * * * * * * * * */
        if ($perm_assignment) {
            $new_perms = [];
            // first, check current perms
            foreach($video->perms as $perm) {
                if ($perm->user_id == $user->id) {
                    // retain my own perms
                    $new_perms[] = $perm->toArray();
                }
            }

            // collect update perms
            $perms = $json['data']['perms'] ?? [];
            foreach ($perms as $perm) {
                // one cannot change its own perms
                if ($perm['user_id'] != $user->id) {
                    $new_perms[] = $perm;
                }
            }


            // clear out all perms and set the new perms
            VideosUserPerms::deleteBySQL('video_id = ?', [$video->id]);

            foreach ($new_perms as $perm) {
                $uperm = new VideosUserPerms();
                $uperm->video_id = $video->id;
                $uperm->setData($perm);
                $uperm->store();
            }
        }

        /* * * * * * * * * * * * * * * * * * * *
         *   L I N K   P E R M I S S I O N S   *
         * * * * * * * * * * * * * * * * * * * */

        // clear out all links
        VideosShares::deleteBySQL('video_id = ?', [$video->id]);

        // only add share links it is globally allowed
        if ($sharing) {
            // set new links
            $shares = $json['data']['shares'] ?? [];
            foreach ($shares as $share) {
                if (isset($share['is_new'])) {
                    $share['video_id'] = $video->id;
                    $share['token'] = VideosShares::generateToken();
                    $share['uuid'] = VideosShares::generateUuid();
                    unset($share['is_new']);
                }
                $nshare = new VideosShares();
                $nshare->setData($share);
                $nshare->store();
            }
        }

        $video = Videos::findByToken($token);

        return $this->createResponse([
            'perms'  => $video->perms->toSanitizedArray(),
            'shares' => $video->shares->toArray(),
        ], $response->withStatus(200));
    }
}
