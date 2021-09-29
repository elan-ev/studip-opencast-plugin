<?php

require_once __DIR__.'/../bootstrap.php';

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
        require_once __DIR__ .'/../models/OCCourseModel.class.php';

        $seminars_acl_refresh=OCSeminarACLRefresh::findBySQL(1);

        if($seminars_acl_refresh){
          $re=[];
          foreach ($seminars_acl_refresh as $seminar_acl_refresh) {
            if($seminar_acl_refresh->running){
              if($seminar_acl_refresh->chdate < time() - 3600){
                $seminar_acl_refresh->delete();
              }
            }else{
              $course = Course::find($seminar_acl_refresh->seminar_id);
              if($course){
                $seminar_acl_refresh->running=true;
                $seminar_acl_refresh->store();
                $re[]=$seminar_acl_refresh;
              }else{
                $seminar_acl_refresh->delete();
              }
            }
          }
          foreach($re as $seminar_acl_refresh){
            $dir = str_replace('/public/plugins_packages/elan-ev/OpenCast/cronjobs', '', __DIR__);
            shell_exec('cd '.$dir.'; /usr/bin/php '.__DIR__.'/refresh_acls_cli.php '.$seminar_acl_refresh->seminar_id.' > /dev/null 2>/dev/null &');
          }
        }

        return true;
    }

}
