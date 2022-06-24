<?php

class PlaylistsCest
{
    public function _before(ApiTester $I)
    {
        $I->amHttpAuthenticated('apitester', 'apitester');
    }

    // tests
    public function testCreatePlaylist(ApiTester $I)
    {
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
    }

    public function testDeletePlaylist(ApiTester $I)
    {
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
            'visibility'  => 'internal'
        ];

        $playlist2 = [
            'title'       => 'Meine Videos 2',
            'description' => 'Videoliste 2',
            'visibility'  => 'free'
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
            'visibility'  => 'internal'
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

    public function testEditingOfAccesibleForeignPlaylist(ApiTester $I)
    {
        // first, create a new playlist
        $playlist = [
            'title'       => 'Meine Videos' ,
            'description' => 'Videoliste',
            'visibility'  => 'internal'
        ];

        $playlist2 = [
            'title'       => 'Meine Videos 2' ,
            'description' => 'Videoliste 2',
            'visibility'  => 'free'
        ];

        $response = $I->sendPostAsJson('/playlists', $playlist);

        $I->seeResponseCodeIs(201);
        $I->seeResponseIsJson();

        $I->seeResponseContainsJson($playlist);
        $I->seeResponseContainsJson(['users' => [['perm' => 'owner']]]);

        list($token) = $I->grabDataFromResponseByJsonPath('$.token');

        // give write perms to other user
        $response = $I->sendPutAsJson('/playlists/' . $token .'/user', [
            'username' => "apitester_autor1",
            'perm'     => 'write'
        ]);
        $I->seeResponseCodeIs(200);

        // then, try to edit it as a different user
        $I->amHttpAuthenticated('apitester_autor1', 'apitester_autor1');

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
            'visibility'  => 'internal'
        ];

        $playlist2 = [
            'title'       => 'Meine Videos 2' ,
            'description' => 'Videoliste 2',
            'visibility'  => 'free'
        ];

        $response = $I->sendPostAsJson('/playlists', $playlist);

        $I->seeResponseCodeIs(201);
        $I->seeResponseIsJson();

        $I->seeResponseContainsJson($playlist);
        $I->seeResponseContainsJson(['users' => [['perm' => 'owner']]]);

        list($token) = $I->grabDataFromResponseByJsonPath('$.token');

        // give write perms to other user
        $response = $I->sendPutAsJson('/playlists/' . $token .'/user', [
            'username' => "apitester_autor1",
            'perm'     => 'read'
        ]);
        $I->seeResponseCodeIs(200);

        // then, try to edit it as a different user
        $I->amHttpAuthenticated('apitester_autor1', 'apitester_autor1');

        $response = $I->sendPutAsJson('/playlists/' . $token, $playlist2);
        $I->seeResponseCodeIs(500);
    }

    public function testRevokingAccessToPlaylist(ApiTester $I)
    {
        // first, create a new playlist
        $playlist = [
            'title'       => 'Meine Videos' ,
            'description' => 'Videoliste',
            'visibility'  => 'internal'
        ];

        $playlist2 = [
            'title'       => 'Meine Videos 2' ,
            'description' => 'Videoliste 2',
            'visibility'  => 'free'
        ];

        $response = $I->sendPostAsJson('/playlists', $playlist);

        $I->seeResponseCodeIs(201);
        $I->seeResponseIsJson();

        $I->seeResponseContainsJson($playlist);
        $I->seeResponseContainsJson(['users' => [['perm' => 'owner']]]);

        list($token) = $I->grabDataFromResponseByJsonPath('$.token');

        // give write perms to other user
        $response = $I->sendPutAsJson('/playlists/' . $token .'/user', [
            'username' => "apitester_autor1",
            'perm'     => 'write'
        ]);
        $I->seeResponseCodeIs(200);

        // remove write perms for user
        $response = $I->sendDelete('/playlists/' . $token .'/user/apitester_autor1');
        $I->seeResponseCodeIs(204);

        // then, try to edit it as a different user
        $I->amHttpAuthenticated('apitester_autor1', 'apitester_autor1');

        $response = $I->sendPutAsJson('/playlists/' . $token, $playlist2);
        $I->seeResponseCodeIs(500);
    }
}
