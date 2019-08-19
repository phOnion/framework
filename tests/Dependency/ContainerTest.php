<?php
namespace Tests\Dependency;

use Onion\Framework\Dependency\Container;
use Onion\Framework\Dependency\Exception\ContainerErrorException;
use Onion\Framework\Dependency\Exception\UnknownDependency;
use Onion\Framework\Dependency\Interfaces\FactoryBuilderInterface;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use stdClass;
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

class ContainerTest extends \PHPUnit\Framework\TestCase
{
    public function testHasParameterCheck()
    {
        $container = new Container( ['bar' => 'baz']);
        $this->assertFalse($container->has('foo'));
        $this->assertFalse($container->has('bar'));
        $this->assertFalse($container->has(1));
    }

    public function testRetrievalOfInvokables()
    {
        $container = new Container([
            'invokables' =>  [
                \stdClass::class => \stdClass::class
            ]
        ]);

        $this->assertTrue($container->has(\stdClass::class));
        $this->assertInstanceOf(\stdClass::class, $container->get(\stdClass::class));
        $this->assertNotSame($container->get(\stdClass::class), $container->get(\stdClass::class));
    }

    public function testRetrievalOfInvokablesWithBadMapping()
    {
        $container = new Container( [
            'invokables' =>  [
                \stdClass::class => 1
            ]
        ]);

        $this->expectExceptionMessage('Unable to resolve');
        $this->expectException(ContainerExceptionInterface::class);
        $container->get(\stdClass::class);
    }

    public function testRetrievalWhenUsingAFactory()
    {
        $container = new Container( [
            'factories' =>  [
                \stdClass::class => FactoryStub::class
            ]
        ]);

        $this->assertTrue($container->has(\stdClass::class));
        $this->assertInstanceOf(\stdClass::class, $container->get(\stdClass::class));
        $this->assertNotSame($container->get(\stdClass::class), $container->get(\stdClass::class));
    }

    public function testExceptionOnNonExistingEntry()
    {
        $container = new Container( []);
        $this->expectExceptionMessage('Unable to resolve');
        $this->expectException(NotFoundExceptionInterface::class);
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

        $this->expectExceptionMessage('Registered factory for \'stdClass\' must be a valid FQCN');
        $this->expectException(ContainerExceptionInterface::class);
        $container = new Container([
            'factories' =>  [
                \stdClass::class => true,
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

        $this->expectExceptionMessage('Factory for \'stdClass\' does not implement any of Dependency\\Interfaces');
        $this->expectException(ContainerExceptionInterface::class);
        $container = new Container(
             [
                'factories' =>  [
                    \stdClass::class => \stdClass::class
                ]
            ]
        );
        $this->assertInstanceOf(\stdClass::class, $container->get(\stdClass::class));
    }

    public function testExceptionWhenInvokableIsStringButNotAClass()
    {
        $this->expectExceptionMessage('Unable to resolve');
        $this->expectException(UnknownDependency::class);
        $container = new Container(
             [
                'invokables' =>  [
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

        $this->expectExceptionMessage('Unable to verify that "stdClass" is of type "SplFixedArray"');
        $this->expectException(ContainerExceptionInterface::class);
        $container = new Container([
            'invokables' =>  [
                \SplFixedArray::class => \stdClass::class
            ]
        ]);

        $container->get(\SplFixedArray::class);
    }

    public function testDependencyResolutionFromReflection()
    {
        $container = new Container([]);
        $this->assertInstanceOf(DependencyD::class, $container->get(DependencyD::class));
    }

    public function testDependencyTypeResolutionFromReflectionException()
    {
        $this->expectException(ContainerErrorException::class);
        $this->expectExceptionMessage('c(' . DependencyC::class . ')');
        $container = new Container([]);
        $container->get(DependencyA::class);
    }

    public function testDependencyLookupWhenBoundToInterface()
    {
        $container = new Container( [
            'invokables' =>  [
                DependencyC::class => DependencyD::class
            ]
        ]);

        $this->assertInstanceOf(DependencyB::class, $container->get(DependencyB::class));
    }

    public function testDependencyWithParameterOfUnknownType()
    {
        $container = new Container( []);

        $this->expectException(ContainerExceptionInterface::class);
        $container->get(DependencyE::class);
    }

    public function testUnknownInterfaceResolution()
    {
        $container = new Container( []);
        $this->expectException(ContainerExceptionInterface::class);
        $container->get(DependencyF::class);
    }

    public function testExceptionOnConstructorParameterNotAvailable()
    {
        $container = new Container([]);
        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('foo(mixed)');
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
        $container->get(DependencyG::class);
    }

    public function testCreationFromFactoryWithInvalidReslut()
    {
        $class = new class implements FactoryInterface {
            public function build(\Psr\Container\ContainerInterface $container)
            {
                // nothing is returned
            }
        };
        $container = new Container([
            'factories' => [
                \StdClass::class => get_class($class),
            ],
            'invokables' => [
                get_class($class) => $class,
            ]
        ]);
        $this->assertTrue($container->has(\StdClass::class));
        $this->expectException(ContainerErrorException::class);
        $this->expectExceptionMessage('No factory available');
        $container->get(\StdClass::class);
    }

    public function testExceptionOnHasInvalidKey()
    {
        $container = new Container([]);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Provided key must be a string');
        $this->assertFalse($container->has(new \stdClass));
    }

    public function testExceptionOnGetInvalidKey()
    {
        $container = new Container([]);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Provided key must be a string');
        $container->get(new \stdClass);
    }

    public function testFactoryBuilderCreation()
    {
        $class = new class implements FactoryBuilderInterface {
            public function build(\Psr\Container\ContainerInterface $container, string $key): FactoryInterface
            {
                return new class ($key) implements FactoryInterface {
                    private $key;
                    public function __construct(string $name)
                    {
                        $this->key = $name;
                    }

                    public function build(ContainerInterface $container) {
                        return $container->get("Tests\\Dependency\\Doubles\\Dependency{$this->key}");
                    }
                };
            }
        };
        $container = new Container([
            'factories' => [
                'D' => get_class($class),
            ],
        ]);
        $this->assertTrue($container->has('D'));
        $this->assertInstanceOf(DependencyD::class, $container->get('D'));
    }

    public function testClosureFactory()
    {
        $container = new Container([
            'factories' => [
                'D' => function () {
                    return new DependencyD;
                }
            ]
        ]);

        $this->assertTrue($container->has('D'));
        $this->assertInstanceOf(DependencyD::class, $container->get('D'));
    }

    public function testNonExistingDependency()
    {
        $container = new Container([]);
        $this->expectException(ContainerErrorException::class);
        $container->get(DependencyJ::class);
    }
}
