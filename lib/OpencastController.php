<?php

namespace Opencast;

use Psr\Container\ContainerInterface;

class OpencastController
{
    /**
     * Der Konstruktor.
     *
     * @param ContainerInterface $container der Dependency Container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
}
