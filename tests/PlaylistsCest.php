<?php

class PlaylistsCest
{
    private $opencast_url;
    private $config_id;
    private $api_token;
    private $opencast_admin_user;
    private $opencast_admin_password;

    private $dozent_name;
    private $author_name;
    private $author_password;

    public function _before(ApiTester $I)
    {
        $config = $I->getConfig();

        $this->opencast_url = $config['opencast_url'];
        $this->config_id = $config['config_id'];
        $this->api_token = $config['api_token'];
        $this->opencast_admin_user = $config['opencast_admin_user'];
        $this->opencast_admin_password = $config['opencast_admin_password'];
        $this->dozent_name = $config['dozent_name'];
        $this->author_name = $config['author_name'];
        $this->author_password = $config['author_password'];

        $I->amHttpAuthenticated($config['dozent_name'], $config['dozent_password']);
    }

    // tests
    public function testCreatePlaylist(ApiTester $I)
    {
        $playlist = [
            'title'       => 'Meine Videos' ,
            'description' => 'Videoliste',
            'visibility'  => 'internal',
            'config_id'   => $this->config_id,
        ];

        $response = $I->sendPostAsJson('/playlists', $playlist);
        $I->seeResponseCodeIs(201);
        $I->seeResponseIsJson();

        $I->seeResponseContainsJson($playlist);
        $I->seeResponseContainsJson(['users' => [['perm' => 'owner']]]);

        list($service_playlist_id) = $I->grabDataFromResponseByJsonPath('$.service_playlist_id');

        // Check if user has correct playlist role
        $response = $I->sendGetAsJson('/opencast/user/' . $this->dozent_name, ['token' => $this->api_token]);

        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();

        $I->seeResponseContainsJson([
            'username' => $this->dozent_name,
            'roles' => [
                'PLAYLIST_' . $service_playlist_id . '_write',
            ]
        ]);

        // Check ACLs in Opencast

        // Login as opencast admin
        $I->amHttpAuthenticated($this->opencast_admin_user, $this->opencast_admin_password);

        $response = $I->sendGetAsJson($this->opencast_url . '/api/playlists/' . $service_playlist_id);
        $I->seeResponseContainsJson(['accessControlEntries' => [
            ['allow' => true, 'role' => 'PLAYLIST_' . $service_playlist_id . '_read', 'action' => 'read'],
            ['allow' => true, 'role' => 'PLAYLIST_' . $service_playlist_id . '_write', 'action' => 'read'],
            ['allow' => true, 'role' => 'PLAYLIST_' . $service_playlist_id . '_write', 'action' => 'write'],
        ]]);
    }

    public function testDeletePlaylist(ApiTester $I)
    {
        $playlist = [
            'title'       => 'Meine Videos' ,
            'description' => 'Videoliste',
            'visibility'  => 'internal',
            'config_id'   => $this->config_id,
        ];

        $response = $I->sendPostAsJson('/playlists', $playlist);
        $I->seeResponseCodeIs(201);
        $I->seeResponseIsJson();

        $I->seeResponseContainsJson($playlist);
        $I->seeResponseContainsJson(['users' => [['perm' => 'owner']]]);

        list($token) = $I->grabDataFromResponseByJsonPath('$.token');

        $response = $I->sendDelete('/playlists/' . $token);
        $I->seeResponseCodeIs(204);
    }

    public function testUpdatePlaylist(ApiTester $I)
    {
        // first, create a new playlist
        $playlist = [
            'title'       => 'Meine Videos' ,
            'description' => 'Videoliste',
            'visibility'  => 'internal',
            'config_id'   => $this->config_id,
        ];

        $playlist2 = [
            'title'       => 'Meine Videos 2',
            'description' => 'Videoliste 2',
            'visibility'  => 'free',
            'config_id'   => $this->config_id,
        ];

        $response = $I->sendPostAsJson('/playlists', $playlist);

        $I->seeResponseCodeIs(201);
        $I->seeResponseIsJson();

        $I->seeResponseContainsJson($playlist);
        $I->seeResponseContainsJson(['users' => [['perm' => 'owner']]]);

        list($token) = $I->grabDataFromResponseByJsonPath('$.token');

        // then, try to update it
        $response = $I->sendPutAsJson('/playlists/' . $token, $playlist2);
        $I->seeResponseCodeIs(200);

        $I->seeResponseContainsJson($playlist2);
    }

    public function testShowPlaylist(ApiTester $I)
    {
        // first, create a new playlist
        $playlist = [
            'title'       => 'Meine Videos' ,
            'description' => 'Videoliste',
            'visibility'  => 'internal',
            'config_id'   => $this->config_id,
        ];

        $response = $I->sendPostAsJson('/playlists', $playlist);

        $I->seeResponseCodeIs(201);
        $I->seeResponseIsJson();

        $I->seeResponseContainsJson($playlist);
        $I->seeResponseContainsJson(['users' => [['perm' => 'owner']]]);

        list($token) = $I->grabDataFromResponseByJsonPath('$.token');

        // then, try to get it
        $response = $I->sendGet('/playlists/' . $token);
        $I->seeResponseCodeIs(200);

        $I->seeResponseContainsJson($playlist);
    }

    public function testAddVideo(ApiTester $I)
    {
        /*
        // Create a playlist
        $playlist = [
            'title'       => 'Meine Videos' ,
            'description' => 'Videoliste',
            'visibility'  => 'internal'
        ];

        $response = $I->sendPostAsJson('/playlists', $playlist);
        $I->seeResponseCodeIs(201);
        $I->seeResponseIsJson();

        $I->seeResponseContainsJson($playlist);
        $I->seeResponseContainsJson(['users' => [['perm' => 'owner']]]);

        list($token_playlist) = $I->grabDataFromResponseByJsonPath('$.token');

        // Create a video
        // TODO
        list($token_video) = $I->grabDataFromResponseByJsonPath('$.token');

        // Add video to playlist
        $response = $I->sendPut('/playlists/' . $token_playlist . '/video/' . $token_video);
        $I->seeResponseCodeIs(204);
        */
    }

    public function testDeleteVideo(ApiTester $I)
    {
        /*
        // Create a playlist
        $playlist = [
            'title'       => 'Meine Videos' ,
            'description' => 'Videoliste',
            'visibility'  => 'internal'
        ];

        $response = $I->sendPostAsJson('/playlists', $playlist);
        $I->seeResponseCodeIs(201);
        $I->seeResponseIsJson();

        $I->seeResponseContainsJson($playlist);
        $I->seeResponseContainsJson(['users' => [['perm' => 'owner']]]);

        list($token_playlist) = $I->grabDataFromResponseByJsonPath('$.token');

        // Create a video
        // TODO
        list($token_video) = $I->grabDataFromResponseByJsonPath('$.token');

        // Add video to playlist
        $response = $I->sendPut('/playlists/' . $token_playlist . '/video/' . $token_video);
        $I->seeResponseCodeIs(204);

        // Remove video from playlist
        $response = $I->sendDelete('/playlists/' . $token_playlist . '/video/' . $token_video);
        $I->seeResponseCodeIs(204);
        */
    }

    public function testEditingOfAccesibleForeignPlaylist(ApiTester $I)
    {
        // first, create a new playlist
        $playlist = [
            'title'       => 'Meine Videos' ,
            'description' => 'Videoliste',
            'visibility'  => 'internal',
            'config_id'   => $this->config_id,
        ];

        $playlist2 = [
            'title'       => 'Meine Videos 2' ,
            'description' => 'Videoliste 2',
            'visibility'  => 'free',
            'config_id'   => $this->config_id,
        ];

        $response = $I->sendPostAsJson('/playlists', $playlist);

        $I->seeResponseCodeIs(201);
        $I->seeResponseIsJson();

        $I->seeResponseContainsJson($playlist);
        $I->seeResponseContainsJson(['users' => [['perm' => 'owner']]]);

        list($token) = $I->grabDataFromResponseByJsonPath('$.token');

        // give write perms to other user
        $response = $I->sendPutAsJson('/playlists/' . $token .'/user', [
            'username' => $this->author_name,
            'perm'     => 'write'
        ]);
        $I->seeResponseCodeIs(200);

        // then, try to edit it as a different user
        $I->amHttpAuthenticated($this->author_name, $this->author_password);

        $response = $I->sendPutAsJson('/playlists/' . $token, $playlist2);
        $I->seeResponseCodeIs(200);

        $I->seeResponseContainsJson($playlist2);
    }

    public function testEditingOfInaccesibleForeignPlaylist(ApiTester $I)
    {
        // first, create a new playlist
        $playlist = [
            'title'       => 'Meine Videos' ,
            'description' => 'Videoliste',
            'visibility'  => 'internal',
            'config_id'   => $this->config_id,
        ];

        $playlist2 = [
            'title'       => 'Meine Videos 2' ,
            'description' => 'Videoliste 2',
            'visibility'  => 'free',
            'config_id'   => $this->config_id,
        ];

        $response = $I->sendPostAsJson('/playlists', $playlist);

        $I->seeResponseCodeIs(201);
        $I->seeResponseIsJson();

        $I->seeResponseContainsJson($playlist);
        $I->seeResponseContainsJson(['users' => [['perm' => 'owner']]]);

        list($token) = $I->grabDataFromResponseByJsonPath('$.token');

        // give write perms to other user
        $response = $I->sendPutAsJson('/playlists/' . $token .'/user', [
            'username' => $this->author_name,
            'perm'     => 'read'
        ]);
        $I->seeResponseCodeIs(200);

        // then, try to edit it as a different user
        $I->amHttpAuthenticated($this->author_name, $this->author_password);

        $response = $I->sendPutAsJson('/playlists/' . $token, $playlist2);
        $I->seeResponseCodeIs(500);
    }

    public function testRevokingAccessToPlaylist(ApiTester $I)
    {
        // first, create a new playlist
        $playlist = [
            'title'       => 'Meine Videos' ,
            'description' => 'Videoliste',
            'visibility'  => 'internal',
            'config_id'   => $this->config_id,
        ];

        $playlist2 = [
            'title'       => 'Meine Videos 2' ,
            'description' => 'Videoliste 2',
            'visibility'  => 'free',
            'config_id'   => $this->config_id,
        ];

        $response = $I->sendPostAsJson('/playlists', $playlist);

        $I->seeResponseCodeIs(201);
        $I->seeResponseIsJson();

        $I->seeResponseContainsJson($playlist);
        $I->seeResponseContainsJson(['users' => [['perm' => 'owner']]]);

        list($token) = $I->grabDataFromResponseByJsonPath('$.token');

        // give write perms to other user
        $response = $I->sendPutAsJson('/playlists/' . $token .'/user', [
            'username' => $this->author_name,
            'perm'     => 'write'
        ]);
        $I->seeResponseCodeIs(200);

        // remove write perms for user
        $response = $I->sendDelete('/playlists/' . $token .'/user/' . $this->author_name);
        $I->seeResponseCodeIs(204);

        // then, try to edit it as a different user
        $I->amHttpAuthenticated($this->author_name, $this->author_password);

        $response = $I->sendPutAsJson('/playlists/' . $token, $playlist2);
        $I->seeResponseCodeIs(500);
    }
}
