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

        try {
            $series = UserSeries::getSeries($user->id);

            if (empty($series)) {
                $series = UserSeries::createSeries($user->id);
            } else {
                $series = $series[0];
            }
        } catch (\Throwable $th) {
            // We do nothing here as to return a null series to display warning.
        }

        return $this->createResponse([
            'series_id' => $series['series_id'] ?? null
        ], $response);
    }
}
