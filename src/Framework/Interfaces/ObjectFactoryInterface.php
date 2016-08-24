<?php
/**
 * PHP Version 5.6.0
 *
 * @category Factory
 * @package  Onion\Framework\Interfaces
 * @author   Dimitar Dimitrov <daghostman.dd@gmail.com>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://github.com/phOnion/framework
 */

namespace Onion\Framework\Interfaces;

use Interop\Container\ContainerInterface;

interface ObjectFactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @return object
     */
    public function __invoke(ContainerInterface $container);

}
