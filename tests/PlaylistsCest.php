<?php

class PlaylistsCest
{
    private $config_id;

    private $author_name;
    private $author_password;

    public function _before(ApiTester $I)
    {
        $config = $I->getConfig();

        $this->config_id = $config['config_id'];
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
