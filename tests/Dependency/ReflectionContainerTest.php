<?php

namespace Tests\Dependency;

use Onion\Framework\Dependency\Exception\ContainerErrorException;
use Onion\Framework\Dependency\Exception\UnknownDependencyException;
use Onion\Framework\Dependency\Interfaces\DelegateContainerInterface;
use Onion\Framework\Dependency\ReflectionContainer;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use Tests\Dependency\Doubles\DependencyA;
use Tests\Dependency\Doubles\DependencyB;
use Tests\Dependency\Doubles\DependencyC;
use Tests\Dependency\Doubles\DependencyD;
use Tests\Dependency\Doubles\DependencyL;
use Tests\Dependency\Doubles\DependencyM;
use Tests\Dependency\Doubles\DependencyN;

class ReflectionContainerTest extends TestCase
{
    use ProphecyTrait;

    private ContainerInterface $container;
    public function setUp(): void
    {
        $this->container = new ReflectionContainer();
    }

    public function testNonExistentClassName()
    {
        $this->expectException(ContainerErrorException::class);
        $this->expectExceptionMessage(
            "Provided key 'foo' is not a FQN of a class or could not be auto-loaded"
        );

        $this->assertFalse($this->container->has('foo'));
        (new ReflectionContainer())->get('foo');
    }

    public function testNoConstructorResolution()
    {
        $this->assertTrue($this->container->has(DependencyD::class));
        $this->assertInstanceOf(
            DependencyD::class,
            (new ReflectionContainer)->get(DependencyD::class)
        );
    }

    public function testUnavailableDependency()
    {
        $this->expectException(UnknownDependencyException::class);
        $this->expectExceptionMessage(sprintf(
            "Unable to resolve %s: Unable to resolve %s: Unable to resolve non-nullable type '\$c(%s)'",
            DependencyA::class,
            DependencyB::class,
            DependencyC::class,
        ));

        $this->assertTrue($this->container->has(DependencyA::class));
        $this->container->get(DependencyA::class);
    }

    public function testNullableDependency()
    {
        $this->assertTrue($this->container->has(DependencyM::class));
        $this->assertInstanceOf(
            DependencyM::class,
            $this->container->get(DependencyM::class),
        );
    }

    public function testOptionalDependency()
    {
        $this->assertTrue($this->container->has(DependencyN::class));
        $this->assertInstanceOf(
            DependencyN::class,
            $this->container->get(DependencyN::class)
        );
    }

    public function testFailOnIntersectionTypes()
    {
        $this->assertTrue($this->container->has(DependencyL::class));
        $this->expectException(UnknownDependencyException::class);
        $this->expectExceptionMessage(sprintf(
            "Unable to resolve %s: Missing \$x(%s | %s)",
            DependencyL::class,
            DependencyA::class,
            DependencyB::class,
        ));
        $this->container->get(DependencyL::class);
    }

    public function testHandlingOfDelegateExceptions()
    {
        $delegate = $this->prophesize(DelegateContainerInterface::class);
        $delegate->has(Argument::type('string'))->shouldBeCalled()->willReturn(true);
        $delegate->get(Argument::type('string'))->shouldBeCalled()->willThrow(new ContainerErrorException('test'));
        $container = new ReflectionContainer();
        $container->attach($delegate->reveal());

        $this->expectException(ContainerErrorException::class);
        $this->expectExceptionMessage(sprintf('Unable to build dependency %s', DependencyA::class));


        $container->get(DependencyA::class);
    }
}
