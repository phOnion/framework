<?php

declare(strict_types=1);

namespace Onion\Framework\Dependency;

use Onion\Framework\Dependency\Traits\ContainerTrait;
use Onion\Framework\Dependency\Traits\WrappingContainerTrait;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Onion\Framework\Dependency\Interfaces\WrappingContainerInterface;
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
class CacheContainer implements ContainerInterface, WrappingContainerInterface
{
    use ContainerTrait;
    use WrappingContainerTrait;

    /**
     * The cache backend in which to store the dependencies
     *
     * @var CacheInterface
     */
    private $cache;

    /**
     * A list of keys that are excluded from the caching and
     * will always be retrieved from the resolved container.
     * This is to allow the construction of dependencies that
     * might change on some factors external to the application.
     *
     * @var string[]
     */
    private $blacklist;

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
     * @param array<array-key, string> $blacklist List of keys to not include in the cache
     */
    public function __construct(ContainerInterface $container, CacheInterface $cache, array $blacklist = [])
    {
        $this->wrap($container);
        $this->cache = $cache;
        $this->blacklist = $blacklist;
    }

    /**
     * @inheritdoc
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Onion\Framework\Dependency\Exception\ContainerErrorException
     */
    public function get($id)
    {
        if ($this->cache->has($id)) {
            return $this->cache->get($id);
        }

        $dependency = $this->getWrappedContainer()->get($id);
        if (!\in_array($id, $this->blacklist, true)) {
            $this->cache->set($id, $dependency);
        }

        return $dependency;
    }

    /**
     * @inheritdoc
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function has($id): bool
    {
        return $this->cache->has($id) || $this->getWrappedContainer()->has($id);
    }
}
