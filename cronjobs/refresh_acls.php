<?php

require_once __DIR__ . '/../bootstrap.php';

use Opencast\Models\OCSeminarACLRefresh;
use Opencast\LTI\OpencastLTI;

class RefreshACLS extends CronJob
{

  public static function getName()
  {
    return _('Opencast - "ACL-Refresh"');
  }

  public static function getDescription()
  {
    return _('Opencast: Aktualisiert die ACLs und die Episoden Sichtbarkeit');
  }

  public function execute($last_result, $parameters = array())
  {
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
          if ($seminar_acl_refresh->chdate < time() - 3600) {
            $seminar_acl_refresh->delete();
          }
        } else {
          $course = Course::find($seminar_acl_refresh->seminar_id);
          if ($course) {
            $seminar_acl_refresh->running = true;
            $seminar_acl_refresh->store();
            $re[] = $seminar_acl_refresh;
          } else {
            $seminar_acl_refresh->delete();
          }
        }
      }
      foreach ($re as $seminar_acl_refresh) {
        $pid = pcntl_fork();

        if ($pid == -1) {
          throw new Exception('Error forking...');
        } else if ($pid == 0) {
          $this->execute_task($seminar_acl_refresh);
          exit();
        }
      }
    }

    return true;
  }

  private function execute_task($seminar_acl_refresh)
  {
    $this->prepare(); //need to create new mysql connection (General error: 2014)
    $seminar_acl_refresh = OCSeminarACLRefresh::find($seminar_acl_refresh->seminar_id);
    OpencastLTI::updateEpisodeVisibility($seminar_acl_refresh->seminar_id);
    OpencastLTI::setAcls($seminar_acl_refresh->seminar_id);
    $seminar_acl_refresh->delete();
  }

  private function prepare()
  {
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
      echo $exception;
    }
  }
}
