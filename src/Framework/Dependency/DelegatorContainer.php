<?php
/**
 * PHP Version 5.6.0
 *
 * @category Dependency-Injection
 * @package  Onion\Framework\Dependency
 * @author   Dimitar Dimitrov <daghostman.dd@gmail.com>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://github.com/phOnion/framework
 */
namespace Onion\Framework\Dependency;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Interop\Container\Exception\NotFoundException;

/**
 * Class DelegatorContainer
 */
class DelegatorContainer implements ContainerInterface
{
    /**
     * Holds all container between which the delegation
     * should happen.
     *
     * @var ContainerInterface[]
     */
    protected $containers = [];

    /**
     * Add a container to be used when attempting to resolve
     * the dependencies
     *
     * @param ContainerInterface $container Container to add
     *
     * @return $this
     */
    public function pushContainer(ContainerInterface $container)
    {
        $this->containers[] = $container;

        return $this;
    }

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $key Identifier of the entry to look for.
     *
     * @throws NotFoundException|Exception\UnknownDependency  No entry was found
     * for this identifier.
     * @throws ContainerException|Exception\ContainerErrorException Error while
     * retrieving the entry.
     *
     * @return mixed Entry.
     */
    public function get($key)
    {
        foreach ($this->containers as $container) {
            if ($container->has($key)) {
                return $container->get($key);
            }
        }

        throw new Exception\UnknownDependency(
            sprintf(
                'Dependency "%s" is not registered with any container',
                $key
            )
        );
    }

    /**
     * Returns true if any of the containers contains an entry
     * for the given identifier. Returns false otherwise.
     *
     * @param string $key Identifier of the entry to look for.
     *
     * @return boolean
     */
    public function has($key)
    {
        foreach ($this->containers as $container) {
            if ($container->has($key)) {
                return true;
            }
        }

        return false;
    }
}
