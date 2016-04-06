<?php

namespace model;

use Slim\Container;
use MongoDB\Collection;
use Predis\Client;

class MainModel
{
    public $container;

    /**
     * MainModel constructor.
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $collection
     * @return Collection
     */
    public function mongo(string $collection): Collection
    {
        return $this->container->get('mongo')->{$collection};
    }

    /**
     * @return Client
     */
    public function redis(): Client
    {
        return $this->container->get('redis');
    }
}
