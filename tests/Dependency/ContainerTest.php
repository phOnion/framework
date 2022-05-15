<?php

namespace Tests\Dependency;

use Onion\Framework\Dependency\Container;
use Onion\Framework\Dependency\Exception\ContainerErrorException;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Tests\Dependency\Doubles\DependencyA;
use Tests\Dependency\Doubles\DependencyB;
use Tests\Dependency\Doubles\DependencyC;
use Tests\Dependency\Doubles\DependencyD;
use Tests\Dependency\Doubles\DependencyE;
use Tests\Dependency\Doubles\DependencyF;
use Tests\Dependency\Doubles\DependencyG;
use Tests\Dependency\Doubles\DependencyH;
use Tests\Dependency\Doubles\DependencyI;
use Tests\Dependency\Doubles\DependencyJ;
use Tests\Dependency\Doubles\FactoryStub;
use Onion\Framework\Dependency\Exception\UnknownDependencyException;
use Onion\Framework\Dependency\Interfaces\ContainerInterface;
use Onion\Framework\Dependency\Interfaces\ContextFactoryInterface;
use Onion\Framework\Dependency\Interfaces\ServiceProviderInterface;
use RuntimeException;
use SplQueue;
use stdClass;

class ContainerTest extends \PHPUnit\Framework\TestCase
{
    public function testHasParameterCheck()
    {
        $container = new Container(['bar' => 'baz']);
        $this->assertFalse($container->has('foo'));
        $this->assertFalse($container->has('bar'));
        $this->assertFalse($container->has(1));
    }

    public function testRetrievalOfInvokables()
    {
        $container = new Container();
        $container->bind(\stdClass::class, \stdClass::class);

        $this->assertTrue($container->has(\stdClass::class));
        $this->assertInstanceOf(\stdClass::class, $container->get(\stdClass::class));
        $this->assertNotSame($container->get(\stdClass::class), $container->get(\stdClass::class));
    }

    public function testRetrievalByAlias()
    {
        $container = new Container();
        $container->bind(stdClass::class, stdClass::class);
        $container->alias('f', stdClass::class);

        $this->assertTrue($container->has('f'));
        $this->assertInstanceOf(\stdClass::class, $container->get('f'));
    }

    public function testRetrievalOfInvokablesWithBadMapping()
    {
        $container = new Container();
        $container->bind(\stdClass::class, 1);

        $this->expectExceptionMessage("Provided key '1' is not a FQN");
        $this->expectException(ContainerErrorException::class);
        $container->get(\stdClass::class);
    }

    public function testRetrievalWhenUsingAFactory()
    {
        $container = new Container([
            'factories' =>  [
                \stdClass::class => FactoryStub::class
            ],
        ]);

        $this->assertTrue($container->has(FactoryStub::class));
        $this->assertTrue($container->has(\stdClass::class));
        $this->assertInstanceOf(\stdClass::class, $container->get(\stdClass::class));
        $this->assertNotSame($container->get(\stdClass::class), $container->get(\stdClass::class));
    }

    public function testExceptionOnNonExistingEntry()
    {
        $container = new Container();
        $this->expectExceptionMessage("Provided key 'foo' is not a FQN");
        $this->expectException(UnknownDependencyException::class);
        $container->get('foo');
    }

    public function testExceptionWhenFactoryDoesNotImplementFactoryInterface()
    {
        if (ini_get('zend.assertions') === '-1') {
            $this->markTestSkipped('In production mode assertions probably are disabled and this test will fail');
        }

        if (ini_get('assert.exception') === '0') {
            $this->markTestSkipped('The "assert.exception" should be set to "1" to throw the exception');
        }

        $this->expectExceptionMessage("Provided key '1' is not a FQN");
        $this->expectException(ContainerErrorException::class);
        $container = new Container();
        $container->bind(\stdClass::class, true);

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

        $this->expectExceptionMessage("Provided key 'FooBar' is not a FQN");
        $this->expectException(ContainerErrorException::class);
        $container = new Container();
        $container->bind(\stdClass::class, 'FooBar');

        $this->assertInstanceOf(\stdClass::class, $container->get(\stdClass::class));
    }

    public function testExceptionWhenInvokableIsStringButNotAClass()
    {
        $this->expectExceptionMessage("Provided key 'FooBarDoesNotExistMan' is not a FQN");
        $this->expectException(UnknownDependencyException::class);
        $container = new Container();
        $container->bind(\stdClass::class, 'FooBarDoesNotExistMan');

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

        $this->expectExceptionMessage('Unable to verify that "stdClass" is of type "SplFixedArray"');
        $this->expectException(RuntimeException::class);
        $container = new Container();
        $container->bind(\SplFixedArray::class, \stdClass::class);

        $container->get(\SplFixedArray::class);
    }

    public function testDependencyResolutionFromReflection()
    {
        $container = new Container([]);
        $this->assertInstanceOf(DependencyD::class, $container->get(DependencyD::class));
    }

    public function testDependencyTypeResolutionFromReflectionException()
    {
        $this->expectException(UnknownDependencyException::class);
        $this->expectExceptionMessage(sprintf(
            "Unable to resolve %s: Unable to resolve %s: Unable to resolve non-nullable type '\$c(%s)'",
            DependencyA::class,
            DependencyB::class,
            DependencyC::class,
        ));
        $container = new Container([]);
        $container->get(DependencyA::class);
    }

    public function testDependencyLookupWhenBoundToInterface()
    {

        $container = new Container();
        $container->bind(DependencyC::class, DependencyD::class);

        $this->assertInstanceOf(DependencyB::class, $container->get(DependencyB::class));
    }

    public function testDependencyWithParameterOfUnknownType()
    {
        $container = new Container([]);

        $this->expectException(ContainerErrorException::class);
        $container->get(DependencyE::class);
    }

    public function testUnknownInterfaceResolution()
    {
        $container = new Container([]);
        $this->expectException(ContainerErrorException::class);
        $container->get(DependencyF::class);
    }

    public function testExceptionOnConstructorParameterNotAvailable()
    {
        $container = new Container([]);
        $this->expectException(UnknownDependencyException::class);
        $this->expectExceptionMessage(sprintf('Unable to resolve %s: Missing $foo(unknown)', DependencyH::class));
        $container->get(DependencyH::class);
    }

    public function testRetrievalOfEmptyConstructorArgs()
    {
        $container = new Container([]);
        $this->assertInstanceOf(DependencyI::class, $container->get(DependencyI::class));
    }

    public function testExceptionWhenKeyDoesNotExist()
    {
        $container = new Container([]);
        $this->expectException(ContainerErrorException::class);
        $this->expectExceptionMessage(sprintf(
            'Unable to resolve %s: Missing $testMockName(string)',
            DependencyG::class,
        ));
        $container->get(DependencyG::class);
    }

    public function testCreationFromFactoryWithInvalidResult()
    {
        $class = new class implements FactoryInterface
        {
            public function build(\Psr\Container\ContainerInterface $container): mixed
            {
                return false;
            }
        };
        $container = new Container();
        $container->bind(\stdClass::class, $class);

        $this->assertTrue($container->has(\stdClass::class));
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No factory available');
        $container->get(\stdClass::class);
    }

    public function testFactoryBuilderCreation()
    {
        $class = new class implements ContextFactoryInterface
        {
            public function build(\Psr\Container\ContainerInterface $container, string $key = null): object
            {
                return $container->get("Tests\\Dependency\\Doubles\\Dependency{$key}");
            }
        };
        $container = new Container();
        $container->bind('D', new $class);
        $this->assertTrue($container->has('D'));
        $this->assertInstanceOf(DependencyD::class, $container->get('D'));
    }

    public function testClosureFactory()
    {
        $container = new Container();
        $container->bind('D', fn () => new DependencyD);

        $this->assertTrue($container->has('D'));
        $this->assertInstanceOf(DependencyD::class, $container->get('D'));
    }

    public function testNonExistingDependency()
    {
        $container = new Container([]);
        $this->expectException(ContainerErrorException::class);
        $container->get(DependencyJ::class);
    }

    public function testServiceProviderRegistration()
    {
        $container = new Container();
        $container->register(new class implements ServiceProviderInterface
        {
            public function register(ContainerInterface $provider): void
            {
                $provider->singleton(stdClass::class, fn () => new stdClass);
                $provider->bind('foo', fn () => new stdClass);
            }

            public function boot(ContainerInterface $provider): void
            {
                $provider->singleton('bar', fn () => new SplQueue());
                $provider->singleton('baz', new SplQueue());
                $provider->extend('bar', function (SplQueue $queue) {
                    $queue->enqueue(1);

                    return $queue;
                });

                $provider->extend('baz', function (SplQueue $queue) {
                    $queue->enqueue(1);

                    return $queue;
                });
            }
        });

        $this->assertInstanceOf(stdClass::class, $container->get(stdClass::class));
        $this->assertSame($container->get(stdClass::class), $container->get(stdClass::class));
        $this->assertInstanceOf(SplQueue::class, $container->get('bar'));
        $this->assertCount(1, $container->get('bar'));
        $this->assertEmpty($container->get('baz'));
    }
}
