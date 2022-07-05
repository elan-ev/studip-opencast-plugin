<?php

class CoursesCest
{
    private $course_id = 'a07535cf2f8a72df33c12ddfa4b53dde';

    public function _before(ApiTester $I)
    {
        $I->amHttpAuthenticated('apitester', 'apitester');
    }

    // tests
    public function testAddPlaylist(ApiTester $I)
    {
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

        list($token) = $I->grabDataFromResponseByJsonPath('$.token');

        // Add playlist to course
        $response = $I->sendPut('/courses/' . $this->course_id . '/playlist/' . $token);
        $I->seeResponseCodeIs(204);
       
    }

    public function testRemovePlaylist(ApiTester $I)
    {
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

        list($token) = $I->grabDataFromResponseByJsonPath('$.token');

        // Add playlist to course
        $response = $I->sendPut('/courses/' . $this->course_id . '/playlist/' . $token);
        $I->seeResponseCodeIs(204);

        // Remove playlist from course
        $response = $I->sendDelete('/courses/' . $this->course_id . '/playlist/' . $token);
        $I->seeResponseCodeIs(204);
    }

}
