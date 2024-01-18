<?php

namespace Opencast;

use Opencast\Providers\StudipServices;

class RouteMap
{
    public function __construct(\Slim\App $app)
    {
        $this->app = $app;
    }

    public function __invoke()
    {
        $container = $this->app->getContainer();

        $this->app->group('', [$this, 'authenticatedRoutes'])
            ->add(new Middlewares\Authentication($container[StudipServices::AUTHENTICATOR]))
            ->add(new Middlewares\RemoveTrailingSlashes);

        $this->app->group('', [$this, 'adminRoutes'])
            ->add(new Middlewares\AdminPerms($container))
            ->add(new Middlewares\Authentication($container[StudipServices::AUTHENTICATOR]))
            ->add(new Middlewares\RemoveTrailingSlashes);

        $this->app->group('/opencast', [$this, 'opencastRoutes'])
            ->add(new Middlewares\TokenAuthentication($container['api-token']))
            ->add(new Middlewares\RemoveTrailingSlashes);
    }

    /**
     * Routes which every user can call
     */
    public function authenticatedRoutes()
    {
        // User routes
        $this->app->get('/user', Routes\User\UserShow::class);
        $this->app->get('/user/search/{search_term}', Routes\User\UserList::class);
        $this->app->get('/user/series', Routes\User\UserSeriesShow::class);

        // Video routes
        $this->app->get('/videos', Routes\Video\VideoList::class);

        $this->app->put('/videos/{token}', Routes\Video\VideoUpdate::class);
        $this->app->put('/videos/{token}/restore', Routes\Video\VideoRestore::class);
        $this->app->delete('/videos/{token}', Routes\Video\VideoDelete::class);
        $this->app->post('/videos/{episode_id}', Routes\Video\VideoAdd::class);

        $this->app->post('/videos/{token}/report', Routes\Video\VideoReport::class);
        $this->app->post('/videos/{token}/playlists', Routes\Video\VideoAddToPlaylist::class);

        $this->app->get('/videos/{token}/captions', Routes\Video\VideoCaptions::class);
        $this->app->get('/videos/{token}/shares', Routes\Video\VideoSharesList::class);
        $this->app->put('/videos/{token}/shares', Routes\Video\VideoSharesUpdate::class);
        $this->app->post('/videos/{course_id}/copy', Routes\Video\VideoCopyToCourse::class);

        // Playlist routes
        $this->app->get('/playlists', Routes\Playlist\PlaylistList::class);
        $this->app->post('/playlists', Routes\Playlist\PlaylistAdd::class);
        $this->app->get('/playlists/{token}', Routes\Playlist\PlaylistShow::class);
        $this->app->put('/playlists/{token}', Routes\Playlist\PlaylistUpdate::class);
        $this->app->delete('/playlists/{token}', Routes\Playlist\PlaylistDelete::class);
        $this->app->post('/playlists/{token}/copy', Routes\Playlist\PlaylistCopy::class);


        $this->app->get('/playlists/{token}/videos', Routes\Playlist\PlaylistVideoList::class);

        $this->app->put('/playlists/{token}/video/{vid_token}', Routes\Playlist\PlaylistAddVideo::class);
        $this->app->delete('/playlists/{token}/video/{vid_token}', Routes\Playlist\PlaylistRemoveVideo::class);

        $this->app->put('/playlists/{token}/user', Routes\Playlist\PlaylistAddUser::class);
        $this->app->delete('/playlists/{token}/user/{username}', Routes\Playlist\PlaylistRemoveUser::class);

        $this->app->put('/playlists/{token}/positions', Routes\Playlist\PlaylistUpdatePositions::class);

        $this->app->get('/playlists/{token}/courses', Routes\Playlist\PlaylistCourses::class);
        $this->app->put('/playlists/{token}/courses', Routes\Playlist\PlaylistAddToCourse::class);

        // Course routes
        $this->app->get('/courses', Routes\Course\MyCourseList::class);

        $this->app->get('/courses/{course_id}/videos', Routes\Course\CourseVideoList::class);

        $this->app->get('/courses/{course_id}/config', Routes\Course\CourseConfig::class);
        $this->app->get('/courses/{course_id}/playlists', Routes\Course\CourseListPlaylist::class);

        $this->app->get('/courses/{course_id}/{semester_filter}/schedule', Routes\Course\CourseListSchedule::class);

        $this->app->post('/courses/{course_id}/playlist/{token}', Routes\Course\CourseAddPlaylist::class);
        $this->app->put('/courses/{course_id}/playlist/{token}', Routes\Course\CourseUpdatePlaylist::class);
        $this->app->delete('/courses/{course_id}/playlist/{token}', Routes\Course\CourseRemovePlaylist::class);

        $this->app->put('/courses/{course_id}/visibility/{visibility}', Routes\Course\CourseSetVisibility::class);
        $this->app->put('/courses/{course_id}/upload/{upload}', Routes\Course\CourseSetUpload::class);                  // TODO: document in api docs

        $this->app->get('/courses/videos', Routes\Course\CourseListForUserVideos::class);
        $this->app->get('/courses/videos/playlist/{token}', Routes\Course\CourseListForPlaylistVideos::class);

        // Schedule
        $this->app->get('/schedule/{course_id}/{termin_id}', Routes\Schedule\ScheduleShow::class);
        $this->app->post('/schedule/{course_id}/{termin_id}', Routes\Schedule\ScheduleAdd::class);
        $this->app->post('/schedulebulk/{course_id}', Routes\Schedule\ScheduleBulk::class);
        $this->app->put('/schedule/{course_id}/{termin_id}', Routes\Schedule\ScheduleUpdate::class);
        $this->app->delete('/schedule/{course_id}/{termin_id}', Routes\Schedule\ScheduleDelete::class);

        $this->app->get('/tags', Routes\Tags\TagListForUser::class);
        $this->app->get('/tags/videos', Routes\Tags\TagListForUserVideos::class);
        $this->app->get('/tags/videos/playlist/{token}', Routes\Tags\TagListForPlaylistVideos::class);
        $this->app->get('/tags/videos/course/{course_id}', Routes\Tags\TagListForCourseVideos::class);

        $this->app->get('/config/simple', Routes\Config\SimpleConfigList::class);
        $this->app->post('/log', Routes\Log\LogEntryCreate::class);

        $this->app->get('/discovery', Routes\DiscoveryIndex::class);
    }

    /**
     * Routes which need admin permissions to call
     */
    public function adminRoutes()
    {
        // TODO: document in api docs
        $this->app->get('/config', Routes\Config\ConfigList::class);                // get a list of all configured servers and their settings
        $this->app->put('/global_config', Routes\Config\ConfigUpdate::class);

        $this->app->post('/config', Routes\Config\ConfigAdd::class);                // create new config
        $this->app->get('/config/{id}', Routes\Config\ConfigShow::class);           // get config with itd
        $this->app->put('/config/{id}', Routes\Config\ConfigEdit::class);           // update existing config
        $this->app->delete('/config/{id}', Routes\Config\ConfigDelete::class);      // delete existing config
    }

    /**
     * Routes called by opencast with token
     */
    public function opencastRoutes()
    {
        $this->app->get('/user/{username}', Routes\Opencast\UserRoles::class);
    }
}
