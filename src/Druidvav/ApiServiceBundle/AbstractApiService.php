<?php

namespace Druidvav\ApiServiceBundle;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Persistence\ObjectManager;
use LogicException;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

abstract class AbstractApiService implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    protected function get(string $id): ?object
    {
        return $this->container->get($id);
    }

    public function getDoctrine(): Registry
    {
        if (!$this->container->has('doctrine')) {
            throw new LogicException('The DoctrineBundle is not registered in your application.');
        }

        return $this->container->get('doctrine');
    }

    protected function getEm(): ObjectManager
    {
        return $this->getDoctrine()->getManager();
    }
}
