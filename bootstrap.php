<?php
/* Composer autoloader */
require_once __DIR__ .'/vendor/autoload.php';

/* Stud.IP dependencies*/
require_once 'lib/models/Institute.class.php';
require_once 'lib/raumzeit/raumzeit_functions.inc.php';
require_once 'vendor/trails/trails.php';

/* cronjobs */
require_once 'lib/classes/CronJob.class.php';

// Courseware Block
require_once 'lib/BlockTypes/OpencastBlock.php';

// adding observer
NotificationCenter::addObserver('Opencast\Models\Videos', 'parseEvent', 'OpencastVideoSync');