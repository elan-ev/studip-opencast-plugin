<?php

namespace Opencast\Routes\Playlist;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Opencast\OpencastTrait;
use Opencast\OpencastController;
use Opencast\Models\Playlists;
use Opencast\Models\PlaylistSeminars;
use Opencast\Models\Filter;
use Opencast\Models\Tags;

/**
 * Find the playlists for the passed user
 */
class PlaylistList extends OpencastController
{
    use OpencastTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $user;

        $params = $request->getQueryParams();

        // find all playlists, the current user has access to
        $playlists = Playlists::getUserPlaylists(new Filter($params), $user->id);

        $playlist_list = [];
        foreach ($playlists['playlists'] as $playlist) {
            $playlist['mkdate'] = ($playlist['mkdate'] == '0000-00-00 00:00:00')
            ? 0 : \strtotime($playlist['mkdate']);
            $playlist_list[$playlist->id] = $playlist->toSanitizedArray();
        }

        $courses_ids = PlaylistSeminars::getUserPlaylistsCourses();

        return $this->createResponse([
            'playlists' => @array_values($playlist_list) ?: [],
            'count'     => $playlists['count'],
            'tags'      => Tags::getUserPlaylistsTags(),
            'courses'   => PlaylistSeminars::getCoursesArray($courses_ids),
        ], $response);
    }
}
