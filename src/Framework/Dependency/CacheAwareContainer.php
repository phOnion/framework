<?php declare(strict_types=1);
namespace Onion\Framework\Dependency;

use Onion\Framework\Dependency\Exception\ContainerErrorException;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * Class CacheAwareContainer
 *
 * A cache-aware container that should speed up dependency
 * resolution by storing everything resolved inside the
 * provided cache. This is a production optimization and
 * it's use while developing is discouraged.
 *
 * @package Onion\Framework\Dependency
 */
class CacheAwareContainer implements ContainerInterface
{
    /**
     * A container which holds the dependencies
     *
     * @var ContainerInterface
     */
    private $container;

    /**
     * Factory for lazy initializing the container
     *
     * @var FactoryInterface
     */
    private $containerFactory;

    /**
     * The cache backend in which to store the dependencies
     *
     * @var CacheInterface
     */
    private $cache;

    /**
     * CacheAwareContainer constructor.
     * This is a composition-based extension to the regular container,
     * it receives a factory class that should prevent initialization
     * of the container on every run(which will remove the benefits of
     * the cache) by initializing it only when the dependency is not
     * present in the cache.
     *
     * @param FactoryInterface $factory A factory to build the real container
     * @param CacheInterface $cache Cache in which to store resolved deps
     */
    public function __construct(FactoryInterface $factory, CacheInterface $cache)
    {
        $this->containerFactory = $factory;
        $this->cache = $cache;
    }

    /**
     * Instantiate the container if not and return it
     *
     * @return ContainerInterface
     */
    private function resolveContainer(): ContainerInterface
    {
        if ($this->container === null) {
            $this->container = $this->containerFactory->build($this);
        }

        return $this->container;
    }

    /**
     * @inheritdoc
     */
    public function get($id)
    {
        if ($this->cache->has($id)) {
            return $this->cache->get($id);
        }

        $dependency = $this->resolveContainer()->get($id);
        if ($this->cache->set($id, $dependency)) {
            return $dependency;
        }

        throw new ContainerErrorException(
            "Unable to persist resolved dependency '$id' in cache"
        );
    }

    /**
     * @inheritdoc
     */
    public function has($id): bool
    {
        return $this->cache->has($id) || $this->resolveContainer()->has($id);
    }
}
