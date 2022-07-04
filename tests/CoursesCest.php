<?php

class CoursesCest
{
    public function _before(ApiTester $I)
    {
        $I->amHttpAuthenticated('apitester', 'apitester');
    }

    // tests
    public function testAddPlaylist(ApiTester $I)
    {
        /*
        // TODO create or add a course
        $course_id = null;

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
        $response = $I->sendPut('/courses/' . $course_id . '/playlists/' . $token);
        $I->seeResponseCodeIs(204);
        */
    }

    public function testRemovePlaylist(ApiTester $I)
    {
        /*
        // TODO create or add a course
        $course_id = null;

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
        $response = $I->sendPut('/courses/' . $course_id . '/playlists/' . $token);
        $I->seeResponseCodeIs(204);

        // Remove playlist from course
        $response = $I->sendDelete('/courses/' . $course_id . '/playlists/' . $token);
        $I->seeResponseCodeIs(204);
        */
    }

}
