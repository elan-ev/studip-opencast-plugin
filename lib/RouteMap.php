<?php

namespace Opencast;

use Psr\Container\ContainerInterface;
use Slim\Routing\RouteCollectorProxy;

class RouteMap
{
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke(RouteCollectorProxy $group)
    {
        $group
            ->group("", [$this, "authenticatedRoutes"])
            ->add(
                new Middlewares\Authentication(
                    $this->container->get("studip-authenticator")
                )
            );

        $group
            ->group("", [$this, "adminRoutes"])
            ->add(new Middlewares\AdminPerms($this->container))
            ->add(
                new Middlewares\Authentication(
                    $this->container->get("studip-authenticator")
                )
            );

        $group
            ->group("/opencast", [$this, "opencastRoutes"])
            ->add(
                new Middlewares\TokenAuthentication(
                    $this->container->get("api-token")
                )
            );
    }

    /**
     * Routes which every user can call
     */
    public function authenticatedRoutes(RouteCollectorProxy $group)
    {
        // User routes
        $group->get("/user", Routes\User\UserShow::class);
        $group->get("/user/search/{search_term}", Routes\User\UserList::class);
        $group->get("/user/series", Routes\User\UserSeriesShow::class);

        // Video routes
        $group->get("/videos", Routes\Video\VideoList::class);

        $group->get("/videos/{token}", Routes\Video\VideoShow::class);
        $group->put("/videos/{token}", Routes\Video\VideoUpdate::class);
        $group->put("/videos/{token}/restore", Routes\Video\VideoRestore::class);
        $group->delete("/videos/{token}", Routes\Video\VideoDelete::class);
        $group->post("/videos/{episode_id}", Routes\Video\VideoAdd::class);

        $group->post("/videos/{token}/report", Routes\Video\VideoReport::class);

        $group->get("/videos/{token}/shares", Routes\Video\VideoSharesList::class);
        $group->put("/videos/{token}/shares", Routes\Video\VideoSharesUpdate::class);

        $group->put("/videos/{token}/worldwide_share", Routes\Video\VideoWorldwideShareUpdate::class);

        // Courseware routes
        $group->get("/courseware/videos", Routes\Courseware\CoursewareVideoList::class);

        // Playlist routes
        $group->get("/playlists", Routes\Playlist\PlaylistList::class);
        $group->post("/playlists", Routes\Playlist\PlaylistAdd::class);
        $group->get("/playlists/{token}", Routes\Playlist\PlaylistShow::class);
        $group->put("/playlists/{token}", Routes\Playlist\PlaylistUpdate::class);
        $group->delete("/playlists/{token}", Routes\Playlist\PlaylistDelete::class);
        $group->post("/playlists/{token}/copy", Routes\Playlist\PlaylistCopy::class);

        $group->get("/playlists/{token}/videos", Routes\Playlist\PlaylistVideoList::class);
        $group->put("/playlists/{token}/videos", Routes\Playlist\PlaylistAddVideos::class);
        $group->patch("/playlists/{token}/videos", Routes\Playlist\PlaylistRemoveVideos::class);

        $group->put("/playlists/{token}/user", Routes\Playlist\PlaylistAddUser::class);
        $group->delete("/playlists/{token}/user/{username}", Routes\Playlist\PlaylistRemoveUser::class);

        $group->put("/playlists/{token}/positions", Routes\Playlist\PlaylistUpdatePositions::class);

        $group->get("/playlists/{token}/courses", Routes\Playlist\PlaylistCourses::class);
        $group->put("/playlists/{token}/courses", Routes\Playlist\PlaylistAddToCourse::class);

        // Schedule and Playlists
        $group->post("/playlists/{token}/schedule/{course_id}/{type}", Routes\Playlist\PlaylistScheduleUpdate::class);

        // Course routes
        $group->get("/courses", Routes\Course\MyCourseList::class);

        $group->get("/courses/{course_id}/videos", Routes\Course\CourseVideoList::class);

        $group->get("/courses/{course_id}/config", Routes\Course\CourseConfig::class);
        $group->get("/courses/{course_id}/playlists", Routes\Course\CourseListPlaylist::class);

        $group->get("/courses/{course_id}/{semester_filter}/schedule", Routes\Course\CourseListSchedule::class);

        $group->post("/courses/{course_id}/playlist/{token}", Routes\Course\CourseAddPlaylist::class);
        $group->put("/courses/{course_id}/playlist/{token}", Routes\Course\CourseUpdatePlaylist::class);
        $group->delete("/courses/{course_id}/playlist/{token}", Routes\Course\CourseRemovePlaylist::class);

        $group->put("/courses/{course_id}/upload/{upload}", Routes\Course\CourseSetUpload::class); // TODO: document in api docs
        // TODO: document in api docs?
        $group->put("/courses/{course_id}/episodes_visibility", Routes\Course\CourseSetDefaultVideosVisibility::class);

        $group->get("/courses/videos", Routes\Course\CourseListForUserVideos::class);
        $group->get("/courses/videos/playlist/{token}", Routes\Course\CourseListForPlaylistVideos::class);

        // Schedule
        $group->get("/schedule/{course_id}/{termin_id}", Routes\Schedule\ScheduleShow::class);
        $group->post("/schedule/{course_id}/{termin_id}", Routes\Schedule\ScheduleAdd::class);
        $group->post("/schedulebulk/{course_id}", Routes\Schedule\ScheduleBulk::class);
        $group->put("/schedule/{course_id}/{termin_id}", Routes\Schedule\ScheduleUpdate::class);
        $group->delete("/schedule/{course_id}/{termin_id}", Routes\Schedule\ScheduleDelete::class);

        $group->get("/tags", Routes\Tags\TagListForUser::class);
        $group->get("/tags/videos", Routes\Tags\TagListForUserVideos::class);
        $group->get("/tags/videos/playlist/{token}", Routes\Tags\TagListForPlaylistVideos::class);
        $group->get("/tags/videos/course/{course_id}", Routes\Tags\TagListForCourseVideos::class);

        $group->get("/config/simple", Routes\Config\SimpleConfigList::class);

        $group->get("/discovery", Routes\DiscoveryIndex::class);
    }

    /**
     * Routes which need admin permissions to call
     */
    public function adminRoutes(RouteCollectorProxy $group)
    {
        // TODO: document in api docs
        $group->get("/config", Routes\Config\ConfigList::class); // get a list of all configured servers and their settings
        $group->put("/global_config", Routes\Config\ConfigUpdate::class);

        $group->post("/config", Routes\Config\ConfigAdd::class); // create new config
        $group->get("/config/{id}", Routes\Config\ConfigShow::class); // get config with itd
        $group->put("/config/{id}", Routes\Config\ConfigEdit::class); // update existing config
        $group->put("/config/{id}/{activation}", Routes\Config\ConfigSetActivation::class);
        $group->delete("/config/{id}", Routes\Config\ConfigDelete::class); // delete existing config

        $group->get('/migrate_playlists', Routes\Config\ConfigMigratePlaylists::class);
    }

    /**
     * Routes called by opencast with token
     */
    public function opencastRoutes(RouteCollectorProxy $group)
    {
        $group->get("/user/{username}", Routes\Opencast\UserRoles::class);
    }
}
