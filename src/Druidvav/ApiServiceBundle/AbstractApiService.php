<?php

namespace Druidvav\ApiServiceBundle;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

abstract class AbstractApiService implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    protected function get(string $id)
    {
        return $this->container->get($id);
    }
}
