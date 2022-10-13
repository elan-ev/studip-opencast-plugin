<?php

namespace Opencast\Routes\Video;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
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

        $token = $args['token'];
        $video = Videos::findByToken($token);

        if (empty($video)) {
            throw new Error(_('Das Video kann nicht gefunden werden'), 404);
        }

        $perm = $video->getUserPerm();
        // only users with owner permission are allowed to show/edit shares
        if (empty($perm) || $perm != 'owner')
        {
            throw new \AccessDeniedException();
        }

        $json = $this->getRequestData($request);

        /* * * * * * * * * * * * * * * * * * * *
         *   U S E R   P E R M I S S I O N S   *
         * * * * * * * * * * * * * * * * * * * */
        $new_perms = [];
        // first, check current perms
        foreach($video->perms as $perm) {
            if ($perm->user_id == $user->id) {
                // retain my own perms
                $new_perms[] = $perm->toArray();
            }
        }

        // collect update perms
        foreach ($json['perms'] as $perm) {
            // one cannot change its own perms
            if ($perm['user_id'] != $user->id) {
                $new_perms[] = $perm;
            }
        }

        // clear out all perms and set the new perms
        VideosUserPerms::deletBySQL('video_id = ?', $video->id);

        foreach ($new_perms as $perm) {
            $uperm = new VideosUserPerms();
            $uperm->setData($perm);
            $uperm->store();
        }

        /* * * * * * * * * * * * * * * * * * * *
         *   L I N K   P E R M I S S I O N S   *
         * * * * * * * * * * * * * * * * * * * */

        // clear out all links
        VideosShares::deleteBySQL('video_id = ?', $video->id);

        // set new links
        foreach ($json['shares'] as $share) {
            $nshare = new VideosShares();
            $nshare->setData($share);
            $nshare->store();
        }

        $video = Videos::findByToken($token);

        return $this->createResponse([
            'perms'  => $video->perms->toSanitizedArray(),
            'shares' => $video->shares->toArray(),
        ], $response->withStatus(200));
    }
}
