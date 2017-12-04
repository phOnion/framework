<?php declare(strict_types=1);
namespace Onion\Framework\Dependency;

use Psr\Container\ContainerInterface;
use Onion\Framework\Dependency\Exception\UnknownDependency;

class DelegateContainer implements ContainerInterface
{
    /** @var \ArrayIterator */
    private $containers;
    public function __construct(array $containers)
    {
        $this->containers = new \ArrayIterator(array_filter($containers, function ($c) {
            return ($c instanceof ContainerInterface);
        }));
    }

    public function get($id)
    {
        if ($this->containers->isEmpty()) {
            throw new Exception\UnknownDependency("No containers provided, can't retrieve '$id'");
        }

        foreach ($this->containers as $container) {
            /** @var ContainerInterface $container */
            if ($container->has($id)) {
                return $container->get($id);
            }
        }

        throw new Exception\UnknownDependency("Unable to resolve '$id'");
    }

    public function has($id)
    {
        foreach ($this->containers as $container) {
            /** @var ContainerInterface $container */
            if ($container->has($id)) {
                return true;
            }
        }

        return false;
    }
}
