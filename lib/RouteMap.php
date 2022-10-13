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

        $this->app->get('/discovery', Routes\DiscoveryIndex::class);
    }

    /**
     * Routes which every user can call
     */
    public function authenticatedRoutes()
    {
        // User routes
        $this->app->get('/user', Routes\User\UserShow::class);
        $this->app->get('/user/search/{search_term}', Routes\User\UserList::class);  // TODO: add this route to the API scheme

        // Video routes
        $this->app->get('/videos', Routes\Video\VideoList::class);
        $this->app->put('/videos/{token}', Routes\Video\VideoUpdate::class);
        $this->app->delete('/videos/{token}', Routes\Video\VideoDelete::class);

        $this->app->post('/videos/{token}/report', Routes\Video\VideoReport::class); // TODO: add this route to the API scheme
        $this->app->post('/videos/{token}/courses', Routes\Video\VideoAddToCourse::class);

        $this->app->get('/videos/{token}/shares', Routes\Video\VideoSharesList::class); // TODO: add this route to the API scheme
        $this->app->put('/videos/{token}/shares', Routes\Video\VideoSharesUpdate::class);

        // Playlist routes
        $this->app->get('/playlists', Routes\Playlist\PlaylistList::class);
        $this->app->post('/playlists', Routes\Playlist\PlaylistAdd::class);
        $this->app->get('/playlists/{token}', Routes\Playlist\PlaylistShow::class);
        $this->app->put('/playlists/{token}', Routes\Playlist\PlaylistUpdate::class);
        $this->app->delete('/playlists/{token}', Routes\Playlist\PlaylistDelete::class);

        $this->app->put('/playlists/{token}/video/{vid_token}', Routes\Playlist\PlaylistAddVideo::class);
        $this->app->delete('/playlists/{token}/video/{vid_token}', Routes\Playlist\PlaylistRemoveVideo::class);
        $this->app->put('/playlists/{token}/user', Routes\Playlist\PlaylistAddUser::class);
        $this->app->delete('/playlists/{token}/user/{username}', Routes\Playlist\PlaylistRemoveUser::class);

        $this->app->put('/playlists/{token}/positions', Routes\Playlist\PlaylistUpdatePositions::class);

        $this->app->get('/playlists/{token}/courses', Routes\Playlist\PlaylistCourses::class);
        $this->app->put('/playlists/{token}/courses', Routes\Playlist\PlaylistAddToCourse::class);

        // Course routes
        $this->app->get('/courses', Routes\Course\MyCourseList::class);
        $this->app->get('/courses/{course_id}/config', Routes\Course\CourseConfig::class);
        $this->app->get('/courses/{course_id}/playlists', Routes\Course\CourseListPlaylist::class);
        $this->app->get('/courses/{course_id}/{semester_filter}/schedule', Routes\Course\CourseListSchedule::class);
        $this->app->put('/courses/{course_id}/playlist/{token}', Routes\Course\CourseAddPlaylist::class);
        $this->app->put('/courses/{course_id}/{visibility}', Routes\Course\CourseSetVisibility::class);
        $this->app->delete('/courses/{course_id}/playlist/{token}', Routes\Course\CourseRemovePlaylist::class);

        // Schedule
        $this->app->get('/schedule/{course_id}/{termin_id}', Routes\Schedule\ScheduleShow::class);
        $this->app->post('/schedule/{course_id}/{termin_id}', Routes\Schedule\ScheduleAdd::class);
        $this->app->post('/schedulebulk/{course_id}', Routes\Schedule\ScheduleBulk::class);
        $this->app->put('/schedule/{course_id}/{termin_id}', Routes\Schedule\ScheduleUpdate::class);
        $this->app->delete('/schedule/{course_id}/{termin_id}', Routes\Schedule\ScheduleDelete::class);

        $this->app->get('/tags', Routes\Tags\TagListForUser::class);

        $this->app->get('/config/simple', Routes\Config\SimpleConfigList::class);
        $this->app->post('/log', Routes\Log\LogEntryCreate::class);
    }

    /**
     * Routes which need admin permissions to call
     */
    public function adminRoutes()
    {
        $this->app->get('/config', Routes\Config\ConfigList::class);
        $this->app->put('/config', Routes\Config\ConfigUpdate::class);
        $this->app->post('/config', Routes\Config\ConfigAddEdit::class);

        $this->app->get('/config/{id}', Routes\Config\ConfigShow::class);
        $this->app->put('/config/{id}', Routes\Config\ConfigAddEdit::class);
        $this->app->delete('/config/{id}', Routes\Config\ConfigDelete::class);
    }

    /**
     * Routes called by opencast vith token
     */
    public function opencastRoutes()
    {
        $this->app->get('/user/{username}', Routes\Opencast\UserRoles::class);
    }
}
