<?php
StudipAutoloader::addAutoloadPath(__DIR__, 'ElanEv');
StudipAutoloader::addAutoloadPath(__DIR__ . '/lib', 'Opencast');

// adding observer
NotificationCenter::addObserver('Opencast\Models\Videos', 'parseEvent', 'OpencastVideoSync');
NotificationCenter::addObserver('Opencast\Models\Videos', 'checkEventACL', 'OpencastVideoSync');
NotificationCenter::addObserver('Opencast\Models\VideosUserPerms', 'setPermissions', 'OpencastVideoSync');
NotificationCenter::addObserver('Opencast\Models\Helpers', 'mapEventUserSeriesUserPerms', 'OpencastVideoSync');
NotificationCenter::addObserver('Opencast\Models\Helpers', 'notifyUsers', 'OpencastNotifyUsers');
NotificationCenter::addObserver('Opencast\Models\Helpers', 'adjustVideoPermissionsForNewCourseTutors', 'UserDidEnterCourse');
NotificationCenter::addObserver('Opencast\Models\Helpers', 'revokeCoursePlaylistUserPerms', 'UserDidLeaveCourse');
