<?php
/**
 * @author Dimitar Dimitrov <daghostman.dd@gmail.com>
 */
namespace Tests\Dependency\Doubles;

use Interop\Container\ContainerInterface;
use Onion\Framework\Interfaces\ObjectFactoryInterface;

class FactoryStub implements ObjectFactoryInterface
{
    /**
     * @var object
     */
    protected $returnVal;
    public function __construct($return)
    {
        $this->returnVal = $return;
    }

    /**
     * @param ContainerInterface $container
     * @return object
     */
    public function __invoke(ContainerInterface $container)
    {
        return is_string($this->returnVal) ?
            new $this->returnVal : $this->returnVal;
    }
}
