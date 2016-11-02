<?php
declare(strict_types = 1);
namespace Onion\Framework\Application\Interfaces;

use Interop\Container\ContainerInterface;
use Onion\Framework\Application\Application;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;

interface ModuleInterface extends FactoryInterface
{
    public function build(ContainerInterface $container): Application;
}
