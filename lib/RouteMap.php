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
        $this->app->get('/user', Routes\Users\UsersShow::class);

        $this->app->get('/resources', Routes\Resources\ResourcesList::class);

        $this->app->get('/lti/launch_data', Routes\LTI\LaunchData::class);
        $this->app->get('/opencast/servers', Routes\Opencast\Servers::class);
        $this->app->get('/opencast/allseries/{id}', Routes\Opencast\AllSeries::class);

        $this->app->get('/course/series/{course_id}', Routes\Course\Series::class);
        $this->app->post('/course/series/{course_id}', Routes\Course\SeriesAdd::class);
        $this->app->delete('/course/series/{course_id}/{series_id}', Routes\Course\SeriesDelete::class);

        $this->app->get('/course/upload_xml/{course_id}', Routes\Course\UploadAcl::class);
        $this->app->post('/course/log_upload/{course_id}', Routes\Course\LogUpload::class);

        $this->app->any('/graphql', GraphQL\GraphQL::class);
        ##TEMPLATE##
    }

    public function adminRoutes()
    {
        $this->app->get('/config', Routes\Config\ConfigList::class);
        $this->app->put('/config', Routes\Config\ConfigUpdate::class);

        $this->app->post('/config', Routes\Config\ConfigAddEdit::class);
        $this->app->get('/config/{id}', Routes\Config\ConfigShow::class);
        $this->app->post('/config/check', Routes\Config\ConfigCheck::class);
        $this->app->put('/config/{id}', Routes\Config\ConfigAddEdit::class);
        $this->app->delete('/config/{id}', Routes\Config\ConfigDelete::class);
    }
}
