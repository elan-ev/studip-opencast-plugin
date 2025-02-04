<?php
namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

use GuzzleHttp\Client;

class Api extends \Codeception\Module
{
    protected $requiredFields = [
        'opencast_url',
        'config_id',
        'api_token',
        'opencast_admin_user',
        'opencast_admin_password',
        'dozent_name',
        'dozent_password',
        'course_student',
        'author_name',
        'author_password',
        'course_id',
    ];

    const STUDIP_CLI = __DIR__ . '/../../../../../../../cli/studip';

    public function getConfig(): array {
        return $this->config;
    }

    /**
     * Wait until video is successfully processed
     *
     * @return bool true, if video processing is finished
     */
    public function seeVideoIsProcessed($id): bool {
        $client = new Client();
        $tries = 0;

        while (true) {
            $this->assertLessThan(30, $tries, 'Number of attempts under limit');

            $response = $client->request('GET', $this->config['opencast_url'] . '/api/events/' . $id, [
                'auth' => [$this->config['opencast_admin_user'], $this->config['opencast_admin_password']],
            ]);

            $this->assertEquals(200, $response->getStatusCode(), 'Successfully fetched video processing state');

            $event = json_decode($response->getBody(), true);
            if ($event['processing_state'] == 'SUCCEEDED') {
                return true;
            }

            $tries++;
            sleep(5);
        }
    }

    /**
     * Run studip cronjob
     *
     * @param string $cronjob cronjob description
     */
    public function runCronjob(string $cronjob)
    {
        if (file_exists(self::STUDIP_CLI)) {
            // Run cronjob on host if studip cli exist
            $studip_cli = self::STUDIP_CLI;
            $command = "php $studip_cli cronjobs:execute $(php $studip_cli cronjobs:list | grep '$cronjob' | awk '{print $1}')";
        } else if ($this->config['docker_command']) {
            $command = str_replace('CRONJOB', $cronjob, $this->config['docker_command']);
        } else {
            // Run cronjob in docker container
            $compose_file = __DIR__ . '/../../../.github/docker/docker-compose.yml';
            $command = "docker compose -f $compose_file exec studip bash -c \"php ./cli/studip cronjobs:execute \\$(php ./cli/studip cronjobs:list | grep '$cronjob' | awk '{print \\$1}')\"";
        }
        exec($command, $output, $result_code);

        $this->assertEquals(0, $result_code, 'Cronjob run successful');
    }
}
