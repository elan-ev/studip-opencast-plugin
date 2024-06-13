<?php

namespace Opencast\Routes\Config;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\Errors\AuthorizationFailedException;
use Opencast\Errors\Error;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\REST\Config as OCConfig;
use Opencast\Helpers\PlaylistMigration;

class ConfigMigratePlaylists extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $user;

        $config_id = \Config::get()->OPENCAST_DEFAULT_SERVER;
        if (!PlaylistMigration::isConverted() &&
            version_compare(
                OCConfig::getOCBaseVersion($config_id),
                '16',
                '>='
            )
        ) {
            PlaylistMigration::convert();
        } else {
            throw new \Exception('Migration nicht möglich, falsche Opencastversion oder bereits migriert.');
        }

        return $this->createResponse([], $response);
    }
}
