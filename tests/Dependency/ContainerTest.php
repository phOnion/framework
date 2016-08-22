<?php
/**
 * @author Dimitar Dimitrov <daghostman.dd@gmail.com>
 */

namespace Tests\Dependency;

use Interop\Container\Exception\ContainerException;
use Interop\Container\Exception\NotFoundException;
use Onion\Framework\Dependency\Container;
use Tests\Dependency\Doubles\FactoryStub;

class ContainerTest extends \PHPUnit_Framework_TestCase
{
    public function testHasParameterCheck()
    {
        $container = new Container([]);
        $this->assertFalse($container->has('foo'));
    }

    public function testRetrievalOfInvocables()
    {
        $container = new Container([
            'invokables' => [
                \stdClass::class => new \stdClass // this part is handled by `\Onion\Dependency\Builder`
            ]
        ]);

        $this->assertTrue($container->has(\stdClass::class));
        $this->assertInstanceOf(\stdClass::class, $container->get(\stdClass::class));
        $this->assertNotSame($container->get(\stdClass::class), $container->get(\stdClass::class));
    }

    public function testRetrievalWhenUsingAFactory()
    {
        $factory = new FactoryStub(\stdClass::class);
        $container = new Container([
            'factories' => [
                \stdClass::class => $factory
            ]
         ]);

        $this->assertTrue($container->has(\stdClass::class));
        $this->assertInstanceOf(\stdClass::class, $container->get(\stdClass::class));
        $this->assertNotSame($container->get(\stdClass::class), $container->get(\stdClass::class));
    }

    public function testRetrievalOfSharedDependenciesFromFactory()
    {
        $container = new Container(
            [
                'factories' => [
                    \stdClass::class => new FactoryStub(new \stdClass())
                ],
                'shared' => [
                    \stdClass::class => \stdClass::class
                ]
            ]
        );

        $this->assertTrue($container->has(\stdClass::class));
        $this->assertInstanceOf(
            \stdClass::class,
            $container->get(\stdClass::class)
        );
        $this->assertInstanceOf(
            \stdClass::class,
            $container->get(\stdClass::class)
        );
        $this->assertSame(
            $container->get(\stdClass::class),
            $container->get(\stdClass::class)
        );
    }

    public function testRetrievalOfSharedDependenciesFromInvokables()
    {
        $container = new Container(
            [
                'invokables' => [
                    \stdClass::class => new \stdClass()
                ],
                'shared' => [
                    \stdClass::class => \stdClass::class
                ]
            ]
        );

        $this->assertTrue($container->has(\stdClass::class));
        $this->assertInstanceOf(
            \stdClass::class,
            $container->get(\stdClass::class)
        );
        $this->assertInstanceOf(
            \stdClass::class,
            $container->get(\stdClass::class)
        );
        $this->assertSame(
            $container->get(\stdClass::class),
            $container->get(\stdClass::class)
        );
    }

    public function testExceptionOnNonExistingEntry()
    {
        $container = new Container([]);
        $this->expectException(NotFoundException::class);
        $container->get('foo');
    }

    public function testExceptionWhenIdNotAString()
    {
        $container = new Container([]);
        $this->expectException(ContainerException::class);

        $container->get(new \stdClass());
    }

    public function testExceptionWhenFactoryDoesNotImplementObjectFactoryInterface() {
        $container = new Container([
            'factories' => [
                \stdClass::class => function(){}
            ]
        ]);

        $this->expectException(ContainerException::class);
        $container->get(\stdClass::class);
    }

    public function testExceptionWhenAFactoryIsStringButNotAClass()
    {
        $this->expectException(ContainerException::class);
        $container = new Container(
            [
                'factories' => [
                    \stdClass::class => \stdClass::class
                ]
            ]
        );
        $this->assertInstanceOf(\stdClass::class, $container->get(\stdClass::class));
    }

    public function testExceptionWhenInvokableIsStringButNotAClass()
    {
        $this->expectException(ContainerException::class);
        $container = new Container(
            [
                'invokables' => [
                    \stdClass::class => 'FooBarDoesNotExistMan'
                ]
            ]
        );
        $this->assertInstanceOf(\stdClass::class, $container->get(\stdClass::class));
    }

    public function testExceptionWhenResultIsNotInstanceOfIdentifier()
    {
        $this->expectException(ContainerException::class);
        $container = new Container(
            [
                'invokables' => [
                    \SplFixedArray::class => \stdClass::class
                ]
            ]
        );

        $container->get(\SplFixedArray::class);
    }
}
