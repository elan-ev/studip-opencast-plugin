<?php

namespace Opencast\Routes\User;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\UserSeries;

class UserSeriesShow extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $user;

        $series = UserSeries::getSeries($user->id);

        if (empty($series)) {
            $series = UserSeries::createSeries($user->id);
        }
        else {
            $series = $series[0];
        }

        return $this->createResponse([
            'series_id' => $series['series_id']
        ], $response);
    }
}