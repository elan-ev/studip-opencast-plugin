<?php
/* Stud.IP dependencies*/
require_once 'lib/models/Institute.class.php';
require_once 'lib/raumzeit/raumzeit_functions.inc.php';
require_once 'vendor/trails/trails.php';

/* cronjobs */
require_once 'lib/classes/CronJob.class.php';

/* Courseware Block */
if (\StudipVersion::newerThan('4.6')) {
    require_once 'lib/BlockTypes/OpencastBlock.php';
} else {
    if (!interface_exists('Courseware\CoursewarePlugin')) {
        require_once 'lib/FakeCoursewareInterface.php';
    }
}

StudipAutoloader::addAutoloadPath(__DIR__, 'ElanEv');
StudipAutoloader::addAutoloadPath(__DIR__ . '/lib', 'Opencast');

// adding observer
NotificationCenter::addObserver('Opencast\Models\Videos', 'parseEvent', 'OpencastVideoSync');
NotificationCenter::addObserver('Opencast\Models\Videos', 'checkEventACL', 'OpencastVideoSync');
NotificationCenter::addObserver('Opencast\Models\Videos', 'addToCoursePlaylist', 'OpencastVideoSync');
NotificationCenter::addObserver('Opencast\Models\VideosUserPerms', 'setPermissions', 'OpencastVideoSync');
