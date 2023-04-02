<?php

namespace Tests\Dependency;

use LogicException;
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
use Onion\Framework\Dependency\Interfaces\BootableServiceProviderInterface;
use Onion\Framework\Dependency\Interfaces\ContainerInterface;
use Onion\Framework\Dependency\Interfaces\ContextFactoryInterface;
use Onion\Framework\Dependency\Interfaces\DelegateContainerInterface;
use Onion\Framework\Dependency\Interfaces\ServiceProviderInterface;
use Prophecy\PhpUnit\ProphecyTrait;
use RuntimeException;
use SplQueue;
use stdClass;

class ContainerTest extends \PHPUnit\Framework\TestCase
{
    use ProphecyTrait;
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
        $container = new Container();
        $container->bind(stdClass::class, new FactoryStub);

        $this->assertTrue($container->has(FactoryStub::class));
        $this->assertTrue($container->has(\stdClass::class));
        $this->assertInstanceOf(\stdClass::class, $container->get(\stdClass::class));
        $this->assertNotSame($container->get(\stdClass::class), $container->get(\stdClass::class));
    }

    public function testExceptionOnNonExistingEntry()
    {
        $container = new Container();
        $this->expectExceptionMessage("Unable to resolve dependency 'foo'");
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

    public function testBindingFromFactory()
    {
        $class = new class implements FactoryInterface
        {
            public function build(\Psr\Container\ContainerInterface $container): mixed
            {
                return true;
            }
        };
        $container = new Container();
        $container->bind(\stdClass::class, $class);

        $this->assertTrue($container->has(\stdClass::class));
        $this->assertTrue($container->get(\stdClass::class));
    }

    public function testSingletonFromFactory()
    {
        $class = new class implements FactoryInterface
        {
            public function build(\Psr\Container\ContainerInterface $container): mixed
            {
                return true;
            }
        };
        $container = new Container();
        $container->singleton(\stdClass::class, $class);

        $this->assertTrue($container->has(\stdClass::class));
        $this->assertTrue($container->get(\stdClass::class));
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

    public function testServiceProviderLoadedOnHas()
    {
        $container = new Container();
        $container->register(new class implements ServiceProviderInterface
        {
            public function register(ContainerInterface $provider): void
            {
                $provider->bind('foo', fn () => 'bar');
            }
        });

        $this->assertTrue($container->has('foo'));
    }

    public function testServiceProviderLoadedOnGet()
    {
        $container = new Container();
        $container->register(new class implements ServiceProviderInterface
        {
            public function register(ContainerInterface $provider): void
            {
                $provider->bind('foo', fn () => 'bar');
            }
        });

        $this->assertSame('bar', $container->get('foo'));
    }

    public function testServiceProviderRegistration()
    {
        $container = new Container();
        $container->register(new class implements BootableServiceProviderInterface
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

                $provider->extend('bar', function (SplQueue $queue) {
                    $queue->enqueue(2);

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
        $this->assertCount(2, $container->get('bar'));
        $this->assertEmpty($container->get('baz'));
    }

    public function testDependencyTagging()
    {
        $container = new Container();
        $container->bind('foo', fn () => new stdClass, ['baz']);
        $container->singleton('bar', fn () => new stdClass, ['baz']);
        $container->tag('foo', 'bar');


        $this->assertIsIterable($container->tagged('baz'));
        $this->assertContainsOnlyInstancesOf(stdClass::class, $container->tagged('baz'));
        $this->assertCount(2, $container->tagged('baz'));

        $this->assertIsIterable($container->tagged('bar'));
        $this->assertContainsOnly(stdClass::class, $container->tagged('bar'));
        $this->assertCount(1, $container->tagged('bar'));
    }

    public function testDirectOverwriteOfExistingDependencyException()
    {
        $container = new Container();
        $container->bind('foo', fn () => new stdClass());

        $this->expectException(ContainerErrorException::class);
        $container->bind('foo', fn () => 'baz');
    }

    public function testOverwriteFromServiceProviderWithUnbinding()
    {
        $container = new Container();
        $container->bind('foo', stdClass::class);
        $container->register(new class implements ServiceProviderInterface
        {
            public function register(ContainerInterface $provider): void
            {
                $provider->unbind('foo');
                $provider->bind('foo', SplQueue::class);
            }
        });

        $this->assertInstanceOf(SplQueue::class, $container->get('foo'));
    }

    public function testExceptionWhenUnbindingFromOutsideOfServiceProvider()
    {
        $container = new Container();

        $container->bind('foo', stdClass::class);
        $container->bind('baz', stdClass::class);
        $container->register(new class implements ServiceProviderInterface
        {
            public function register(ContainerInterface $provider): void
            {
                $provider->unbind('baz');
            }
        });
        $this->expectException(LogicException::class);
        $this->assertInstanceOf(stdClass::class, $container->get('foo'));

        $container->unbind('foo');
        $this->assertFalse($container->has('baz'));
        $this->assertTrue($container->has('foo'));
    }

    public function testResolutionDelegation()
    {
        $delegate = $this->prophesize(ContainerInterface::class)
            ->willImplement(DelegateContainerInterface::class);

        $delegate->has('foo')
            ->willReturn(true)
            ->shouldBeCalledOnce();
        $delegate->get('foo')
            ->willReturn('bar')
            ->shouldBeCalledOnce();

        $container = new Container();
        $container->attach($delegate->reveal());

        $this->assertSame('bar', $container->get('foo'));
    }
}
