<?php

class AclCest
{
    private $user;
    private $course_student;
    private $config_id;
    private $course_id;
    private $opencast_rest_url;
    private $api_token;

    public function _before(ApiTester $I)
    {
        $config = $I->getConfig();

        $this->user = $config['user'];
        $this->course_student = $config['course_student'];
        $this->config_id = $config['config_id'];
        $this->course_id = $config['course_id'];
        $this->opencast_rest_url = $config['opencast_rest_url'];
        $this->api_token = $config['api_token'];

        $I->amHttpAuthenticated($config['user'], $config['password']);
    }

    // tests
    public function testPlaylistAcl(ApiTester $I)
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

        // Check ACLs in Opencast
        list($service_playlist_id) = $I->grabDataFromResponseByJsonPath('$.service_playlist_id');

        // Assume same user exist in Opencast with ADMIN permission
        $response = $I->sendGetAsJson($this->opencast_rest_url . '/playlists/' . $service_playlist_id);
        $I->seeResponseContainsJson(['accessControlEntries' => [
            ['allow' => true, 'role' => 'PLAYLIST_' . $service_playlist_id . '_read', 'action' => 'read'],
            ['allow' => true, 'role' => 'PLAYLIST_' . $service_playlist_id . '_write', 'action' => 'read'],
            ['allow' => true, 'role' => 'PLAYLIST_' . $service_playlist_id . '_write', 'action' => 'write'],
        ]]);

        // Check if playlist user roles exists
        $response = $I->sendGetAsJson('/opencast/user/' . $this->user, ['token' => $this->api_token]);

        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();

        $I->seeResponseContainsJson([
            'username' => $this->user,
            'roles' => [
                'PLAYLIST_' . $service_playlist_id . '_write',
            ]
        ]);
    }

    public function testCoursePlaylistAcl(ApiTester $I)
    {
        // Create a playlist
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
        list($service_playlist_id) = $I->grabDataFromResponseByJsonPath('$.service_playlist_id');

        // Add playlist to course
        $response = $I->sendPost('/courses/' . $this->course_id . '/playlist/' . $token);
        $I->seeResponseCodeIs(204);

        // Check if student of course has read access only
        $response = $I->sendGetAsJson('/opencast/user/' . $this->course_student, ['token' => $this->api_token]);

        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();

        $I->seeResponseContainsJson([
            'username' => $this->course_student,
            'roles' => [
                'PLAYLIST_' . $service_playlist_id . '_read',
            ]
        ]);
        $I->dontSeeResponseContainsJson(['roles' => [
            'PLAYLIST_' . $service_playlist_id . '_write',
        ]]);
    }

    public function testRemoveCoursePlaylistAcl(ApiTester $I)
    {
        // Create a playlist
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
        list($service_playlist_id) = $I->grabDataFromResponseByJsonPath('$.service_playlist_id');

        // Add playlist to course
        $response = $I->sendPost('/courses/' . $this->course_id . '/playlist/' . $token);
        $I->seeResponseCodeIs(204);

        // Remove playlist from course
        $response = $I->sendDelete('/courses/' . $this->course_id . '/playlist/' . $token);
        $I->seeResponseCodeIs(204);

        // Check if student of course has no access
        $response = $I->sendGetAsJson('/opencast/user/' . $this->course_student, ['token' => $this->api_token]);

        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();

        $I->dontseeResponseContainsJson([
            'username' => $this->course_student,
            'roles' => [
                'PLAYLIST_' . $service_playlist_id . '_read',
                'PLAYLIST_' . $service_playlist_id . '_write',
            ]
        ]);
    }
}
