<?php
namespace Tests\Dependency;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Onion\Framework\Dependency\Container;
use Onion\Framework\Dependency\Exception\UnknownDependency;
use Tests\Dependency\Doubles\DependencyB;
use Tests\Dependency\Doubles\DependencyC;
use Tests\Dependency\Doubles\DependencyD;
use Tests\Dependency\Doubles\DependencyE;
use Tests\Dependency\Doubles\DependencyF;
use Tests\Dependency\Doubles\DependencyG;
use Tests\Dependency\Doubles\DependencyH;
use Tests\Dependency\Doubles\DependencyI;
use Tests\Dependency\Doubles\FactoryStub;
use Onion\Framework\Dependency\Exception\ContainerErrorException;

class ContainerTest extends \PHPUnit_Framework_TestCase
{
    public function testHasParameterCheck()
    {
        $container = new Container( ['bar' => 'baz']);
        $this->assertFalse($container->has('foo'));
        $this->assertTrue($container->has('bar'));
        $this->assertSame('baz', $container->get('bar'));
    }

    public function testRetrievalOfInvokables()
    {
        $container = new Container( [
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

    public function testRetrievalOfSharedDependenciesFromFactory()
    {
        $container = new Container(
             [
                'factories' =>  [
                    \stdClass::class => FactoryStub::class,
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
                'invokables' =>  [
                    \stdClass::class => \stdClass::class
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

    public function testExceptionOnNonExistingEntry()
    {
        $container = new Container( []);
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

        $this->expectException(ContainerExceptionInterface::class);
        $container = new Container( [
            'factories' =>  [
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

        $this->expectException(ContainerExceptionInterface::class);
        $container = new Container(
             [
                'invokables' =>  [
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
        $container = new Container( []);
        $this->assertInstanceOf(DependencyD::class, $container->get(DependencyD::class));
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

    public function testResolutionBasedOnSimpleVariableName()
    {
        $container = new Container( [
            'name' => 'foo'
        ]);

        $this->assertNotEmpty($container->get(DependencyE::class)->getName());
        $this->assertSame('foo', $container->get(DependencyE::class)->getName());
    }

    public function testResolutionWithComplexVariableName()
    {
        $container = new Container( [
            'test' =>  [
                'mock' =>  [
                    'name' => 'foo'
                ]
            ]
        ]);
        $this->assertTrue($container->has('test.mock.name'));
        $dep = $container->get(DependencyG::class);
        $this->assertNotEmpty($dep->getName());
        $this->assertSame('foo', $dep->getname());
    }

    public function testExceptionWhenComplexResolutionFails()
    {
        $container = new Container( ['foo' =>  ['bar'=> 'baz']]);
        $this->assertFalse($container->has('foo.bar.baz'));
//        $this->expectException(ContainerExceptionInterface::class);
//        $this->expectExceptionMessage('Unable to resolve "foo.bar.baz"');
    }

    public function testExceptionOnComplexResolutionTypeMismatch()
    {
        $container = new Container( ['test' =>  ['mock' =>  ['name' => 5]]]);
        $this->assertSame('5', $container->get(DependencyG::class)->getName());
    }

    public function testUnknownInterfaceResolution()
    {
        $container = new Container( []);
        $this->expectException(ContainerExceptionInterface::class);
        $container->get(DependencyF::class);
    }

    public function testExceptionOnConstructorParameterNotAvailable()
    {
        $container = new Container( []);
        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage(
            'Unable to resolve a class parameter "foo"'
        );
        $container->get(DependencyH::class);
    }

    public function testRetrievalOfEmptyConstructorArgs()
    {
        $container = new Container( []);
        $this->assertInstanceOf(DependencyI::class, $container->get(DependencyI::class));
    }

    public function testRetrievalOfDotStringFromObject()
    {
        $container = new Container( [
            'foo' =>  ['bar' => 'baz']
        ]);
        $this->assertTrue($container->has('foo.bar'));
        $this->assertSame($container->get('foo.bar'), 'baz');
    }

    public function testRetrievalOfDotStringFromArray()
    {
        $container = new Container( [
            'foo' => ['bar' => 'baz']
        ]);
        $this->assertTrue($container->has('foo.bar'));
        $this->assertSame($container->get('foo.bar'), 'baz');
    }

    public function testRetrievalOfDotStringFromNonExistingProp()
    {
        $container = new Container( [
            'foo' => ['bar' => ['baz' => 'foobar']]
        ]);
        $this->assertFalse($container->has('foo.bar.connection'));
        $this->expectException(UnknownDependency::class);
        $this->expectExceptionMessage("Unable to resolve 'connection' of 'foo.bar.connection'");
        $container->get('foo.bar.connection');
    }

    public function testExceptionWhenKeyDoesNotExist()
    {
        $container = new Container( []);
        $this->expectException(ContainerErrorException::class);
        $container->get(DependencyG::class);
    }
}
