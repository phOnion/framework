<?php
namespace Onion\Framework\Dependency\Traits;

use Psr\Container\ContainerInterface;

trait WrappingContainerTrait
{
    private $container;

    public function wrap(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    protected function getWrappedContainer(): ContainerInterface
    {
        return $this->containers;
    }
}
