<?php

namespace Opencast\Routes\Video;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;

class VideoList extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        $test = [
            'id'            => '2',
            'token'	        => 'abcdef1234',
            'config_id'	    => '22',
            'autor'         => 'Günter Jung',          // Get it from oc_video_user_perms
            'contributors'  => 'Miriam Fröhlich',      // Where to get that from?
            'episode'	    => 'abc-def-ghi-123-456',
            'title'	        => 'Testtitel',
            'description'	=> 'Beschreibung',
            'duration'	    => '1230000',
            'views'	        => '3',
            'preview'	    => 'https://studip.me',
            'publication'	=> 'https://studip.me',
            'visibility'	=> 'public',
            'chdate'	    => strtotime('2022-07-07 14:23:51'),
            'mkdate'        => strtotime('2022-07-05 09:12:12')
        ];

        $test2 = $test;
        $test2['id'] = '42';
        $test2['token'] = 'ghijklf1234';

        return $this->createResponse([$test, $test2], $response);
    }
}
