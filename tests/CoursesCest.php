<?php

class CoursesCest
{
    private $config_id;
    private $api_token;
    private $course_student;
    private $course_id;

    public function _before(ApiTester $I)
    {
        $config = $I->getConfig();

        $this->config_id = $config['config_id'];
        $this->api_token = $config['api_token'];
        $this->course_student = $config['course_student'];
        $this->course_id = $config['course_id'];

        $I->amHttpAuthenticated($config['dozent_name'], $config['dozent_password']);
    }

    // tests
    public function testListPlaylists(ApiTester $I)
    {
        // Create a playlist
        $playlist = [
            'title'       => 'Meine Videos' ,
            'description' => 'Videoliste',
            'visibility'  => 'internal',
            'config_id'   => $this->config_id
        ];

        $response = $I->sendPostAsJson('/playlists', $playlist);
        $I->seeResponseCodeIs(201);
        $I->seeResponseIsJson();

        $I->seeResponseContainsJson($playlist);
        $I->seeResponseContainsJson(['users' => [['perm' => 'owner']]]);

        list($token) = $I->grabDataFromResponseByJsonPath('$.token');

        // Add playlist to course
        $response = $I->sendPost('/courses/' . $this->course_id . '/playlist/' . $token);
        $I->seeResponseCodeIs(204);

        $response = $I->sendGet('/courses/' . $this->course_id . '/playlists');
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'title' => $playlist['title'],
            'description' => $playlist['description'],
            'visibility' => 'visible',
            'config_id' => $playlist['config_id'],
        ]);
    }

    public function testAddPlaylist(ApiTester $I)
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

    public function testRemovePlaylist(ApiTester $I)
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

        $roles = ['PLAYLIST_' . $service_playlist_id . '_read', 'PLAYLIST_' . $service_playlist_id . '_write'];
        foreach ($roles as $role) {
            $I->dontseeResponseContainsJson([
                'username' => $this->course_student,
                'roles' => [$role]
            ]);
        }
    }

}
