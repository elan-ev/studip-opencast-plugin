<?php

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\SingleCommandApplication;
use Symfony\Component\Console\Command\Command;

use OpencastApi\Opencast;

require_once __DIR__ . '/../vendor/autoload.php';

// maximum number of concurrent running jobs
$CONCURRENT_JOBS = 50;

class HelperFunctions
{
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

        // get ALL events in Opencast, paginated
        $events = [];
        $offset = 0;
        $limit  = 300;
        $spinner = ['|', '/', '-', '\\'];
        $spinnerIndex = 0;

        $output->write('Fetching events ');
        do {
            $paged_events = $opencastApi->eventsApi->getAll(['limit' => $limit, 'offset' => $offset]);
            $events = array_merge($events, $paged_events['body']);

            // Update and display the spinner
            $output->write("\r" . 'Fetching events ' . $spinner[$spinnerIndex] . ' (' . count($events) . ' so far)');
            $spinnerIndex = ($spinnerIndex + 1) % 4;
            $offset += $limit;
        } while (sizeof($paged_events['body']) > 0);

        $output->writeln(''); // Move to the next line after fetching is complete

        if (empty($events)) {
            // could not retrieve events
            return Command::FAILURE;
        }

        $count = sizeof($events);

        if (empty($events)) {
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
            if ($event->status == 'EVENTS.EVENTS.STATUS.PROCESSED'
                && !empty($event->publication_status)
                && in_array('engage-player', $event->publication_status) === true
            ) {
                if (HelperFunctions::waitForSlot($opencastApi)) {
                    $opencastApi->workflowsApi->run($event->identifier, 'republish-metadata');
                }
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