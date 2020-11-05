<?php
/* Opencast plugin constants */
require_once __DIR__ .'/constants.php';

/* Stud.IP dependencies*/
require_once 'lib/models/Institute.class.php';
require_once 'lib/raumzeit/raumzeit_functions.inc.php';
require_once 'vendor/trails/trails.php';

/* OC plugin dependencies*/
require_once __DIR__ .'/classes/lti/OAuth.php';

$namespaces = [
    'Opencast',
    'Opencast\\Models',
    'Opencast\\LTI',
];
$paths = ['models', 'classes', 'classes/lti', 'classes/OCRestClient'];
foreach($namespaces as $namespace) {
    foreach($paths as $path) {
        StudipAutoloader::addAutoloadPath(__DIR__ . '/' . $path);
        StudipAutoloader::addAutoloadPath(__DIR__ . '/' . $path, $namespace);
    }
}

require_once 'models/OCModel.php';
require_once 'models/OCCourseModel.class.php';
require_once 'models/OCSeriesModel.php';

require_once 'classes/OCPerm.php';

require_once 'classes/OCRestClient/OCRestClient.php';

require_once 'classes/OCRestClient/ACLManagerClient.php';
require_once 'classes/OCRestClient/ArchiveClient.php';
require_once 'classes/OCRestClient/ApiEventsClient.php';
require_once 'classes/OCRestClient/ApiSeriesClient.php';
require_once 'classes/OCRestClient/ApiWorkflowsClient.php';
require_once 'classes/OCRestClient/CaptureAgentAdminClient.php';
require_once 'classes/OCRestClient/IngestClient.php';
require_once 'classes/OCRestClient/OCRestClient.php';
require_once 'classes/OCRestClient/SchedulerClient.php';
require_once 'classes/OCRestClient/SearchClient.php';
require_once 'classes/OCRestClient/SeriesClient.php';
require_once 'classes/OCRestClient/ServicesClient.php';
require_once 'classes/OCRestClient/WorkflowClient.php';


/* cronjobs */
require_once 'lib/classes/CronJob.class.php';
