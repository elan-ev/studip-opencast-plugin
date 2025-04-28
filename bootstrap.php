<?php
StudipAutoloader::addAutoloadPath(__DIR__, 'ElanEv');
StudipAutoloader::addAutoloadPath(__DIR__ . '/lib', 'Opencast');

// adding observer
NotificationCenter::addObserver('Opencast\Models\Videos', 'addToCoursePlaylist', 'OpencastCourseSync');
NotificationCenter::addObserver('Opencast\Models\Videos', 'parseEvent', 'OpencastVideoSync');
NotificationCenter::addObserver('Opencast\Models\Videos', 'checkEventACL', 'OpencastVideoSync');
NotificationCenter::addObserver('Opencast\Models\VideosUserPerms', 'setPermissions', 'OpencastVideoSync');
NotificationCenter::addObserver('Opencast\Models\Helpers', 'notifyUsers', 'OpencastNotifyUsers');

