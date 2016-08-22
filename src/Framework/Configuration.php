<?php
/**
 * PHP Version 5.6.0
 *
 * @category Configuration
 * @package  Onion\Framework
 * @author   Dimitar Dimitrov <daghostman.dd@gmail.com>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://github.com/phOnion/framework
 */
namespace Onion\Framework;

use Interop\Container\Exception\ContainerException;
use Interop\Container\Exception\NotFoundException;

class Configuration implements \Interop\Container\ContainerInterface
{
    protected $configuration = [];

    /**
     * Configuration constructor.
     *
     * @param array|\ArrayObject $configuration Configurations
     */
    public function __construct($configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $key Identifier of the entry to look for.
     *
     * @throws NotFoundException  No entry was found for this identifier.
     * @throws ContainerException Error while retrieving the entry.
     *
     * @return mixed Entry.
     */
    public function get($key)
    {
        if (!$this->has($key)) {
            return null;
        }

        return $this->configuration[$key];
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * @param string $key Identifier of the entry to look for.
     *
     * @return boolean
     */
    public function has($key)
    {
        return array_key_exists($key, $this->configuration);
    }
}
