<?php
namespace Onion\Framework\Dependency\Interfaces;

use Psr\Container\ContainerInterface;

interface WrappingContainerInterface
{
    public function wrap(ContainerInterface $container): void;
}
