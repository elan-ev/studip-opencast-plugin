<?php

class CoursesCest
{
    private $config_id;
    private $course_id;

    public function _before(ApiTester $I)
    {
        $config = $I->getConfig();

        $this->config_id = $config['config_id'];
        $this->course_id = $config['course_id'];

        $I->amHttpAuthenticated($config['user'], $config['password']);
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

        // Add playlist to course
        $response = $I->sendPost('/courses/' . $this->course_id . '/playlist/' . $token);
        $I->seeResponseCodeIs(204);
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

        // Add playlist to course
        $response = $I->sendPost('/courses/' . $this->course_id . '/playlist/' . $token);
        $I->seeResponseCodeIs(204);

        // Remove playlist from course
        $response = $I->sendDelete('/courses/' . $this->course_id . '/playlist/' . $token);
        $I->seeResponseCodeIs(204);
    }

}
