<?php
/**
 * @author Dimitar Dimitrov <daghostman.dd@gmail.com>
 */

namespace Tests\Dependency;

use Interop\Container\Exception\ContainerException;
use Interop\Container\Exception\NotFoundException;
use Onion\Framework\Dependency\Container;
use Onion\Framework\Dependency\Exception\ContainerErrorException;
use Onion\Framework\Dependency\Exception\UnknownDependency;
use Tests\Dependency\Doubles\DependencyA;
use Tests\Dependency\Doubles\DependencyB;
use Tests\Dependency\Doubles\DependencyC;
use Tests\Dependency\Doubles\DependencyD;
use Tests\Dependency\Doubles\DependencyE;
use Tests\Dependency\Doubles\DependencyF;
use Tests\Dependency\Doubles\FactoryStub;

class ContainerTest extends \PHPUnit_Framework_TestCase
{
    public function testHasParameterCheck()
    {
        $container = new Container(['bar' => 'baz']);
        $this->assertFalse($container->has('foo'));
        $this->assertTrue($container->has('bar'));
        $this->assertSame('baz', $container->get('bar'));
    }

    public function testRetrievalOfInvokables()
    {
        $container = new Container([
            'invokables' => [
                \stdClass::class => \stdClass::class
            ]
        ]);

        $this->assertTrue($container->has(\stdClass::class));
        $this->assertInstanceOf(\stdClass::class, $container->get(\stdClass::class));
        $this->assertNotSame($container->get(\stdClass::class), $container->get(\stdClass::class));
    }

    public function testRetrievalOfInvokablesWithBadMapping()
    {
        $container = new Container([
            'invokables' => [
                \stdClass::class => 1
            ]
        ]);

        $this->expectException(ContainerException::class);
        $container->get(\stdClass::class);
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
                    \stdClass::class
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
        if (ini_get('zend.assertions') === '-1') {
            $this->markTestSkipped('In production mode assertions probably are disabled and this test will fail');
        }

        if (ini_get('assert.exception') === '0') {
            $this->markTestSkipped('The "assert.exception" should be set to "1" to throw the exception');
        }

        $container = new Container([]);
        $this->expectException(\InvalidArgumentException::class);

        $container->get(new \stdClass());
    }

    public function testExceptionWhenFactoryDoesNotImplementFactoryInterface()
    {
        if (ini_get('zend.assertions') === '-1') {
            $this->markTestSkipped('In production mode assertions probably are disabled and this test will fail');
        }

        if (ini_get('assert.exception') === '0') {
            $this->markTestSkipped('The "assert.exception" should be set to "1" to throw the exception');
        }

        $this->expectException(ContainerException::class);
        $container = new Container([
            'factories' => [
                \stdClass::class => function () {
                }
            ]
        ]);
        $container->get(\stdClass::class);
    }

    public function testExceptionWhenAFactoryIsStringButNotAClass()
    {
        if (ini_get('zend.assertions') === '-1') {
            $this->markTestSkipped('In production mode assertions probably are disabled and this test will fail');
        }

        if (ini_get('assert.exception') === '0') {
            $this->markTestSkipped('The "assert.exception" should be set to "1" to throw the exception');
        }

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
        $this->expectException(UnknownDependency::class);
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
        if (ini_get('zend.assertions') === '-1') {
            $this->markTestSkipped('In production mode assertions probably are disabled and this test will fail');
        }

        if (ini_get('assert.exception') === '0') {
            $this->markTestSkipped('The "assert.exception" should be set to "1" to throw the exception');
        }

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

    public function testExceptionWhenConstructingWithBadArgument()
    {
        $this->expectException(\TypeError::class);

        new Container(null);
    }

    public function testDependencyResolutionFromReflection()
    {
        $container = new Container([]);
        $this->assertInstanceOf(DependencyD::class, $container->get(DependencyD::class));
    }

    public function testDependencyLookupWhenBoundToInterface()
    {
        $container = new Container([
            'invokables' => [
                DependencyC::class => DependencyD::class
            ]
        ]);

        $this->assertInstanceOf(DependencyB::class, $container->get(DependencyB::class));
    }

    public function testDependencyWithParameterOfUnknownType()
    {
        $container = new Container([]);

        $this->expectException(ContainerException::class);
        $container->get(DependencyE::class);
    }

    public function testUnknownInterfaceResolution()
    {
        $container = new Container([]);
        $this->expectException(ContainerException::class);
        $container->get(DependencyF::class);
    }
}
