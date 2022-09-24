<?php
/* Composer autoloader */
require_once __DIR__ .'/vendor/autoload.php';

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

// adding observer
NotificationCenter::addObserver('Opencast\Models\Videos', 'parseEvent', 'OpencastVideoSync');
NotificationCenter::addObserver('Opencast\Models\Videos', 'checkEventACL', 'OpencastVideoSync');
NotificationCenter::addObserver('Opencast\Models\VideosUserPerms', 'setPermissions', 'OpencastVideoSync');
NotificationCenter::addObserver('Opencast\Models\VideoSeminars', 'videoSeminarEntry', 'OpencastVideoSync');