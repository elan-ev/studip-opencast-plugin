<?php

require_once 'cli/studip_cli_env.inc.php'; //work dir must be studip root folder
require_once __DIR__.'/../bootstrap.php';

use Opencast\Models\OCSeminarACLRefresh;
use Opencast\LTI\OpencastLTI;

set_time_limit(1800); //longer than this is insane ;D

$memory_limit = trim(ini_get('memory_limit')); // increase memory_limit limit
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

$seminar_id=$_SERVER['argv'][1];

$seminar_acl_refresh=OCSeminarACLRefresh::find($seminar_id);

if($seminar_acl_refresh){
  OpencastLTI::updateEpisodeVisibility($seminar_id);
  OpencastLTI::setAcls($seminar_id);
  $seminar_acl_refresh->delete();
}
