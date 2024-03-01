<?php

namespace Opencast;

use DI\Container;
use DI\ContainerBuilder;
use Slim\App;
use StudipPlugin;
use Opencast\Middlewares\RemoveTrailingSlashes;

/**
 * Diese Klasse erstellt eine neue Slim-Applikation und konfiguriert
 * diese rudimentär vor.
 *
 * Dabei werden im `Dependency Container` der Slim-Applikation unter
 * dem Schlüssel `plugin` das Stud.IP-Plugin vermerkt und außerdem
 * eingestellt, dass Fehler des Slim-Frameworks detailliert angezeigt
 * werden sollen, wenn sich Stud.IP im Modus `development` befindet.
 *
 * Darüber hinaus wird eine Middleware installiert, die alle Requests umleitet,
 * die mit einem Schrägstrich enden (und zwar jeweils auf das Pendant
 * ohne Schrägstrich).
 *
 * @see http://www.slimframework.com/
 * @see \Studip\ENV
 * @see \Opencast\Middlewares\RemoveTrailingSlashes
 */
class AppFactory
{
    /**
     * Diese Factory-Methode erstellt die Slim-Applikation und
     * konfiguriert diese wie oben angegeben.
     *
     * @param \StudipPlugin $plugin das Plugin, für die die
     *                              Slim-Applikation erstellt werden soll
     *
     * @return \Slim\App die erstellte Slim-Applikation
     */
    public function makeApp(StudipPlugin $plugin): App
    {
        $container = $this->configureContainer($plugin);
        $app = \Slim\Factory\AppFactory::createFromContainer($container);
        $container->set(App::class, $app);

        $app->add(new RemoveTrailingSlashes());

        $displayErrorDetails = defined('\\Studip\\ENV') && \Studip\ENV === 'development';
        $errorMiddleware = $app->addErrorMiddleware($displayErrorDetails, true, true);
        $errorMiddleware->setDefaultErrorHandler(Errors\ExceptionHandler::class);

        return $app;
    }

    // hier wird der Container konfiguriert
    private function configureContainer($plugin): Container
    {
        $builder = new ContainerBuilder();
        $builder->addDefinitions([
            'plugin' => $plugin,
        ]);
        $builder->addDefinitions(
            __DIR__ . '/Providers/StudipConfig.php',
            __DIR__ . '/Providers/OpencastConstants.php',
            __DIR__ . '/Providers/StudipServices.php',
            __DIR__ . '/Providers/PluginRoles.php',
            __DIR__ . '/Providers/Tokens.php'
        );

        return $builder->build();
    }
}
