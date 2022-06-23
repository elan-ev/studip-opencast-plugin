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

        $this->app->get('/discovery', Routes\DiscoveryIndex::class);
    }

    public function authenticatedRoutes()
    {
        $this->app->get('/user', Routes\User\UserShow::class);

        #Video
        $this->app->get('/video', Routes\Video\Video::class);
        $this->app->get('/video/{id}', Routes\Video\VideoShow::class);
        $this->app->put('/video/{id}', Routes\Video\VideoUpdate::class);
        $this->app->delete('/video/{id}', Routes\Video\VideoDelete::class);

        #Playlist
        $this->app->get('/playlist', Routes\Playlist\Playlist::class);
        $this->app->get('/playlist/{id}', Routes\Playlist\PlaylistShow::class);
        $this->app->put('/playlist/{id}', Routes\Playlist\PlaylistUpdate::class);
        $this->app->delete('/playlist/{id}', Routes\Playlist\PlaylistDelete::class);

        $this->app->get('/lti/launch_data', Routes\LTI\LaunchData::class);

    }

    public function adminRoutes()
    {
        $this->app->get('/config', Routes\Config\ConfigList::class);
        $this->app->put('/config', Routes\Config\ConfigUpdate::class);
        $this->app->post('/config', Routes\Config\ConfigAddEdit::class);

        $this->app->get('/config/{id}', Routes\Config\ConfigShow::class);
        $this->app->put('/config/{id}', Routes\Config\ConfigAddEdit::class);
        $this->app->delete('/config/{id}', Routes\Config\ConfigDelete::class);
    }
}
