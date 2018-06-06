<?php
namespace Tests\Dependency\Doubles;

use Psr\Container\ContainerInterface;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;

class FactoryStub implements FactoryInterface
{
    /**
     * @var object
     */
    protected $returnVal;
    public function __construct($return = \stdClass::class)
    {
        $this->returnVal = $return;
    }

    /**
     * @param ContainerInterface $container
     * @return object
     */
    public function build(ContainerInterface $container)
    {
        return is_string($this->returnVal) ?
            new $this->returnVal : $this->returnVal;
    }
}
