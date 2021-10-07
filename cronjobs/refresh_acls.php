<?php

require_once __DIR__ . '/../bootstrap.php';

use Opencast\Models\OCSeminarACLRefresh;
use Opencast\LTI\OpencastLTI;

class RefreshACLS extends CronJob
{

    /**
     * Return the name of the cronjob.
     */
    public static function getName()
    {
        return _('Opencast - "ACL-Refresh"');
    }

    /**
     * Return the description of the cronjob.
     */
    public static function getDescription()
    {
        return _('Opencast: Aktualisiert die ACLs und die Episoden Sichtbarkeit');
    }

    /**
     * Execute the cronjob.
     *
     * @param mixed $last_result What the last execution of this cronjob
     *                                                     returned.
     * @param Array $parameters Parameters for this cronjob instance which
     *                                                    were defined during scheduling.
     */
    public function execute($last_result, $parameters = array())
    {
        // set memory_limit if present (fixes garuda static memory_limit)
        $memory_limit = trim(ini_get('memory_limit'));
        if ($memory_limit) {
            $last = strtolower($memory_limit[strlen($memory_limit) - 1]);
            $memory_limit = substr($memory_limit, 0, -1);
            switch ($last) {
                case 'g':
                    $memory_limit *= 1024;
                case 'm':
                    $memory_limit *= 1024;
                case 'k':
                    $memory_limit *= 1024;
            }

            if ($memory_limit < 16777216000) {
                ini_set('memory_limit', '16G');
            }
        }

        $seminars_acl_refresh = OCSeminarACLRefresh::findBySQL(1);

        if ($seminars_acl_refresh) {
            $re = [];

            foreach ($seminars_acl_refresh as $seminar_acl_refresh) {
                if ($seminar_acl_refresh->running) {
                    // delete running task that might not be running
                    if ($seminar_acl_refresh->chdate < time() - 3600) {
                        $seminar_acl_refresh->delete();
                    }
                } else {
                    // check if course exists
                    $course = Course::find($seminar_acl_refresh->seminar_id);
                    if ($course) {
                        $seminar_acl_refresh->running = true;
                        $seminar_acl_refresh->store();

                        // store in an array in order not to interrupt the database connection at this time
                        $re[] = $seminar_acl_refresh;
                    } else {
                        $seminar_acl_refresh->delete();
                    }
                }
            }

            // Create forks from the current execution to run the task async
            foreach ($re as $seminar_acl_refresh) {
                $pid = pcntl_fork();
                if ($pid == -1) {
                    throw new Exception('Error forking...');
                } else if ($pid == 0) {
                    $this->execute_task($seminar_acl_refresh);
                    // exit fork after execution
                    exit();
                }
            }

            // we need to create new mysql connection to avoid a "General error: 2006 MySQL server has gone away"
            if ($re) {
                $this->prepare();
            }
        }

        return true;
    }

    /**
     * Executes the fork task to update the EpisodeVisibility and ACLS of the seminar series
     * @param OCSeminarACLRefresh $seminar_acl_refresh 
     */
    private function execute_task($seminar_acl_refresh)
    {
        // we need to create new mysql connection to avoid a "General error: 2014 Cannot execute queries while other unbuffered queries are active."
        $this->prepare();

        OpencastLTI::updateEpisodeVisibility($seminar_acl_refresh->seminar_id);
        OpencastLTI::setAcls($seminar_acl_refresh->seminar_id);
        $seminar_acl_refresh->delete();
    }


    /**
     * Creates new Stud.IP DB Connection (copy from bootstrap.php)
     */
    private function prepare()
    {
        // set default pdo connection
        try {
            DBManager::getInstance()
                ->setConnection(
                    'studip',
                    'mysql:host=' . $GLOBALS['DB_STUDIP_HOST'] .
                        ';dbname=' . $GLOBALS['DB_STUDIP_DATABASE'] .
                        ';charset=utf8mb4',
                    $GLOBALS['DB_STUDIP_USER'],
                    $GLOBALS['DB_STUDIP_PASSWORD']
                );
        } catch (PDOException $exception) {
            throw new Exception($exception);
        }
        // set slave connection
        if (isset($GLOBALS['DB_STUDIP_SLAVE_HOST'])) {
            try {
                DBManager::getInstance()
                    ->setConnection(
                        'studip-slave',
                        'mysql:host=' . $GLOBALS['DB_STUDIP_SLAVE_HOST'] .
                            ';dbname=' . $GLOBALS['DB_STUDIP_SLAVE_DATABASE'] .
                            ';charset=utf8mb4',
                        $GLOBALS['DB_STUDIP_SLAVE_USER'],
                        $GLOBALS['DB_STUDIP_SLAVE_PASSWORD']
                    );
            } catch (PDOException $exception) {
                DBManager::getInstance()->aliasConnection('studip', 'studip-slave');
                throw new Exception($exception);
            }
        } else {
            DBManager::getInstance()->aliasConnection('studip', 'studip-slave');
        }
    }
}
