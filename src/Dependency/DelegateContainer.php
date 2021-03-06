<?php declare(strict_types=1);
namespace Onion\Framework\Dependency;

use function Onion\Framework\Common\merge;
use Onion\Framework\Dependency\Exception\UnknownDependency;
use Onion\Framework\Dependency\Interfaces\AttachableContainer;
use Psr\Container\ContainerInterface;

class DelegateContainer implements ContainerInterface, \Countable
{
    /** @var \ArrayIterator */
    private $containers;

    /** @var ContainerInterface[] */
    public function __construct(array $containers)
    {
        assert(count(array_filter($containers, function (object $container): bool {
            return ($container instanceof ContainerInterface);
        })) === count($containers), new \UnexpectedValueException(
            'Provided array contains non-compliant values - expected ContainerInterface instances'
        ));

        $this->containers = new \ArrayIterator(array_map(function (ContainerInterface $container): ContainerInterface {
            if ($container instanceof AttachableContainer) {
                $container->attach($this);
            }

            return $container;
        }, $containers));
    }

    public function count(): int
    {
        return count($this->containers);
    }

    public function get($id)
    {
        if ($this->containers->count() === 0) {
            throw new Exception\UnknownDependency("No containers provided, can't retrieve '$id'");
        }

        $result = null;
        foreach ($this->containers as $container) {
            /** @var ContainerInterface $container */
            if ($container->has($id)) {
                $hit = $container->get($id);

                if (!is_array($hit)) {
                    return $hit;
                }

                $result = merge($result ?? [], $hit);
            }
        }

        if ($result !== null) {
            return $result;
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
