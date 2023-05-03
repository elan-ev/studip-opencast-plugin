<?php

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\SingleCommandApplication;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Command\Command;

use OpencastApi\Opencast;
use OpencastApi\Rest\OcRestClient;

require_once __DIR__ . '/../vendor/autoload.php';

// maximum number of concurrent running jobs
$CONCURRENT_JOBS = 3;

class HelperFunctions
{
    static function filterForEpisode($episode_id, $acl)
    {
        $possible_roles = [
            'STUDIP_' . $episode_id . '_read',
            'STUDIP_' . $episode_id . '_write',
            'ROLE_ANONYMOUS'
        ];

        $result = [];
        foreach ($acl as $entry) {
            if (in_array($entry['role'], $possible_roles) !== false) {
                $result[] = $entry;
            }
        }

        return $result;
    }

    static function addEpisodeAcl($episode_id, $add_acl, $acl)
    {
        $possible_roles = [
            'STUDIP_' . $episode_id . '_read',
            'STUDIP_' . $episode_id . '_write',
            'ROLE_ANONYMOUS'
        ];

        $result = [];
        foreach ($acl as $entry) {
            if (in_array($entry['role'], $possible_roles) === false) {
                $result[] = $entry;
            }
        }

        return array_merge($result, $add_acl);
    }

    /**
     * Check that the episode has its unique ACL and set it if necessary
     *
     * @Notification OpencastVideoSync
     *
     * @param string                $eventType
     * @param object                $episode
     * @param Opencast\Models\Video $video
     *
     * @return void
     */
    static function checkEventACL($episode_id, $opencastApi)
    {
        // $api_client      = ApiEventsClient::getInstance($video->config_id);
        // $workflow_client = ApiWorkflowsClient::getInstance($video->config_id);

        $response = $opencastApi->eventsApi->getAcl($episode_id);
        if ($response['code'] == 200) {
            $current_acl = json_decode(json_encode($response['body']), true);
        } else {
            return false;
        }

        // one ACL for reading AND for reading and writing
        $acl = [
            [
                'allow'  => true,
                'role'   => 'STUDIP_' . $episode_id .'_read',
                'action' => 'read'
            ],

            [
                'allow'  => true,
                'role'   => 'STUDIP_' . $episode_id .'_write',
                'action' => 'read'
            ],

            [
                'allow'  => true,
                'role'   => 'STUDIP_' . $episode_id .'_write',
                'action' => 'write'
            ]
        ];

        $oc_acl = self::filterForEpisode($episode_id, $current_acl);

        if ($acl <> $oc_acl || true) {
            $new_acl = self::addEpisodeAcl($episode_id, $acl, $current_acl);

            // wait for free slot to process update
            if (HelperFunctions::waitForSlot($opencastApi)) {
                $opencastApi->eventsApi->updateAcl($episode_id, $new_acl);
                $opencastApi->workflowsApi->run($episode_id, 'republish-metadata');
            }
        }

        return true;
    }

    public static function waitForSlot($opencastApi)
    {
        global $CONCURRENT_JOBS;

        // get number of running jobs
        do {
            $response = $opencastApi->eventsApi->getAll([
                'filter' => 'status:EVENTS.EVENTS.STATUS.PROCESSING'
            ]);

            if ($response['code'] == 200) {
                $events = $response['body'];
            } else {
                return false;
            }

            if (sizeof($events) > $CONCURRENT_JOBS) {
                echo 'currently '. sizeof($events) . ' events in process, waiting to drop below '. $CONCURRENT_JOBS ."\r";
                sleep(2);
            }
        } while (sizeof($events) > $CONCURRENT_JOBS);


        // if number of running jobs is below max., return true
        return true;
    }
}

(new SingleCommandApplication())
    ->setName('Convert ACLs to Stud.IP Opencast Plugin V3')
    ->setVersion('1.0')
    ->addArgument('url',  InputArgument::REQUIRED, 'URL to Opencast')
    ->addArgument('user', InputArgument::REQUIRED, 'Opencast administrative user')
    ->addArgument('pass', InputArgument::REQUIRED, 'Opencast administrative user password')
    ->setCode(function (InputInterface $input, OutputInterface $output) {

        $oc_config = [
            'url'      => $input->getArgument('url'),
            'username' => $input->getArgument('user'),
            'password' => $input->getArgument('pass'),
            'timeout'  => 30,
            'connect_timeout' => 30,
        ];

        $opencastApi = new Opencast($oc_config);

        $output->writeln([
            'Using config:',
            print_r($oc_config, 1)
        ]);

        // get ALL events in Opencast
        $response = $opencastApi->eventsApi->getAll();

        if ($response['code'] == 200) {
            $events = $response['body'];
        } else {
            // could not retrieve events
            return Command::FAILURE;
        }

        $count = sizeof($events);

        $output->writeln([
            'Found '. $count .' events to check, starting migration...',
            ''
        ]);

        // iterate over events, check if an update is necessary
        $progressBar = new ProgressBar($output, $count);

        foreach ($events as $event) {
            if ($event->status == 'EVENTS.EVENTS.STATUS.PROCESSED') {
                HelperFunctions::checkEventACL($event->identifier, $opencastApi);
            }

            $progressBar->advance();
        }

        $progressBar->finish();

        $output->writeln([
            '',
            '',
            'Migration finished!',
            ''
        ]);

        return Command::SUCCESS;
})->run();