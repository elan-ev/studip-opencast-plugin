<?php

use GuzzleHttp\Client;

class VideoCest
{
    private $opencast_url;
    private $config_id;
    private $api_token;
    private $opencast_admin_user;
    private $opencast_admin_password;
    private $dozent_name;
    private $course_student;
    private $course_id;

    private $video = [
        'flavor' => 'presenter/source',
        'title' => 'Test with Audio',
        'creator' => 'Test Dozent',
        'identifier' => null,
        'config_id' => null,
        'token' => null,
    ];
    private $playlist_token;

    const CRONJOB_DISCOVER = 'Opencast: Katalogisiert neue Videos aus Opencast.';
    const CRONJOB_QUEUE = 'Opencast: Arbeitet vorgemerkte Aufgaben ab, wie Aktualisierung der Metadaten, ACLs (Sichtbarkeit), etc.';

    public function _before(ApiTester $I)
    {
        $config = $I->getConfig();

        $this->opencast_url = $config['opencast_url'];
        $this->config_id = $config['config_id'];
        $this->api_token = $config['api_token'];
        $this->opencast_admin_user = $config['opencast_admin_user'];
        $this->opencast_admin_password = $config['opencast_admin_password'];
        $this->dozent_name = $config['dozent_name'];
        $this->course_student = $config['course_student'];
        $this->course_id = $config['course_id'];

        $this->video['config_id'] = $this->config_id;

        $I->amHttpAuthenticated($config['dozent_name'], $config['dozent_password']);
    }

    // tests
    public function testIngestVideo(ApiTester $I)
    {
        // Ingest video to opencast
        $client = new Client();
        $response = $client->request('POST', $this->opencast_url . '/ingest/addMediaPackage/fast', [
            'auth' => [$this->opencast_admin_user, $this->opencast_admin_password],
            'multipart' => [
                ['name' => 'flavor', 'contents' => $this->video['flavor']],
                ['name' => 'title', 'contents' => $this->video['title']],
                ['name' => 'creator', 'contents' => $this->video['creator']],
                ['name' => 'BODY', 'contents' => fopen(codecept_data_dir('test-with-audio.mp4'), 'r')],
            ]
        ]);

        $video_xml = simplexml_load_string($response->getBody());
        $this->video['identifier'] = (string) $video_xml->xpath('//wf:mediaPackageId')[0];

        $I->assertEquals($response->getStatusCode(), 200, 'Video is ingested');

        // Add video to studip, fails if video is already added
        $response = $I->sendPostAsJson('/videos/' . $this->video['identifier'], [
            'event' => $this->video,
        ]);

        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();

        $I->seeResponseContainsJson(['event' => [
            'title' => $this->video['title'],
            'episode' => $this->video['identifier'],
            'config_id' => $this->video['config_id'],
        ]]);
        $this->video['token'] = $I->grabDataFromResponseByJsonPath('$.event.token')[0];

        $I->seeVideoIsProcessed($this->video['identifier']);

        // Start cronjobs
        $I->runCronjob(self::CRONJOB_DISCOVER);
        $I->runCronjob(self::CRONJOB_QUEUE);

        $I->seeVideoIsProcessed($this->video['identifier']);
    }

    /**
     * @depends testIngestVideo
     */
    public function testVideoAcl(ApiTester $I)
    {
        // Check if user has correct role
        $response = $I->sendGetAsJson('/opencast/user/' . $this->dozent_name, ['token' => $this->api_token]);

        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();

        $I->seeResponseContainsJson([
            'username' => $this->dozent_name,
            'roles' => [
                $this->video['identifier'] . '_write',
            ]
        ]);

        // Check ACLs in Opencast

        // Login as opencast admin
        $I->amHttpAuthenticated($this->opencast_admin_user, $this->opencast_admin_password);

        $response = $I->sendGetAsJson($this->opencast_url . '/api/events/' . $this->video['identifier'] . '/acl');
        $I->seeResponseContainsJson([
            ['allow' => true, 'role' => $this->video['identifier'] . '_read', 'action' => 'read'],
            ['allow' => true, 'role' => $this->video['identifier'] . '_write', 'action' => 'read'],
            ['allow' => true, 'role' => $this->video['identifier'] . '_write', 'action' => 'write'],
        ]);
    }

    /**
     * @depends testIngestVideo
     */
    public function testCourseVideoAcl(ApiTester $I)
    {
        // Add video to course

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

        list($this->playlist_token) = $I->grabDataFromResponseByJsonPath('$.token');

        // Add playlist to course
        $response = $I->sendPost('/courses/' . $this->course_id . '/playlist/' . $this->playlist_token);
        $I->seeResponseCodeIs(204);

        // Add video to course playlist
        $I->sendPut('/playlists/' . $this->playlist_token . '/videos', [
            'videos' => [$this->video['token']],
            'course_id' => $this->course_id,
        ]);
        $I->seeResponseCodeIs(204);

        // Start cronjobs
        sleep(30);
        $I->runCronjob(self::CRONJOB_QUEUE);

        // Check if lecturer of course has instructor role
        $response = $I->sendGetAsJson('/opencast/user/' . $this->dozent_name, ['token' => $this->api_token]);

        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();

        $I->seeResponseContainsJson([
            'username' => $this->dozent_name,
            'roles' => [
                $this->course_id . '_Instructor',
            ]
        ]);

        $I->dontSeeResponseContainsJson(['roles' => [
            $this->course_id . '_Learner',
        ]]);

        // Check if student of course has learner role only
        $response = $I->sendGetAsJson('/opencast/user/' . $this->course_student, ['token' => $this->api_token]);

        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();

        $I->seeResponseContainsJson([
            'username' => $this->course_student,
            'roles' => [
                $this->course_id . '_Learner',
            ]
        ]);

        $I->dontSeeResponseContainsJson(['roles' => [
            $this->course_id . '_Instructor',
        ]]);


        // Check ACLs in Opencast

        // Ensure video is processed and acls are set
        $I->seeVideoIsProcessed($this->video['identifier']);

        // Login as opencast admin
        $I->amHttpAuthenticated($this->opencast_admin_user, $this->opencast_admin_password);

        $response = $I->sendGetAsJson($this->opencast_url . '/api/events/' . $this->video['identifier'] . '/acl');
        $I->seeResponseContainsJson([
            ['allow' => true, 'role' => $this->video['identifier'] . '_read', 'action' => 'read'],
            ['allow' => true, 'role' => $this->video['identifier'] . '_write', 'action' => 'read'],
            ['allow' => true, 'role' => $this->video['identifier'] . '_write', 'action' => 'write'],
            ['allow' => true, 'role' => $this->course_id . '_Learner', 'action' => 'read'],
            ['allow' => true, 'role' => $this->course_id . '_Instructor', 'action' => 'read'],
            ['allow' => true, 'role' => $this->course_id . '_Instructor', 'action' => 'write'],
        ]);
    }

    /**
     * @depends testCourseVideoAcl
     */
    public function testRemoveCourseVideoAcl(ApiTester $I)
    {
        // Remove video from course playlist
        $I->sendPatch('/playlists/' . $this->playlist_token . '/videos', json_encode([
            'videos' => [$this->video['token']],
            'course_id' => $this->course_id,
        ]));
        $I->seeResponseCodeIs(204);


        // Start cronjobs
        sleep(30);
        $I->runCronjob(self::CRONJOB_QUEUE);

        // Check ACLs in Opencast

        // Ensure video is processed and acls are set
        $I->seeVideoIsProcessed($this->video['identifier']);

        // Login as opencast admin
        $I->amHttpAuthenticated($this->opencast_admin_user, $this->opencast_admin_password);

        $response = $I->sendGetAsJson($this->opencast_url . '/api/events/' . $this->video['identifier'] . '/acl');
        $I->seeResponseContainsJson([
            ['allow' => true, 'role' => $this->video['identifier'] . '_read', 'action' => 'read'],
            ['allow' => true, 'role' => $this->video['identifier'] . '_write', 'action' => 'read'],
            ['allow' => true, 'role' => $this->video['identifier'] . '_write', 'action' => 'write'],
        ]);

        $I->dontSeeResponseContainsJson(['allow' => true, 'role' => $this->course_id . '_Learner', 'action' => 'read']);
        $I->dontSeeResponseContainsJson(['allow' => true, 'role' => $this->course_id . '_Instructor', 'action' => 'read']);
        $I->dontSeeResponseContainsJson(['allow' => true, 'role' => $this->course_id . '_Instructor', 'action' => 'write']);
    }
}
