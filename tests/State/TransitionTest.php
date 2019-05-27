<?php
namespace Tests\State;

use PHPUnit\Framework\TestCase;
use Onion\Framework\State\Transition;

class TransitionTest extends TestCase
{
    public function testBasicInitialization()
    {
        $transition = new Transition('Foo', 'Bar');
        $this->assertSame('foo', $transition->getSource());
        $this->assertSame('bar', $transition->getDestination());
        $this->assertFalse($transition->hasHandler());
        $this->assertEmpty($transition->getArguments());
        $this->assertTrue($transition());
    }

    public function testSuccessHandlerInvoke()
    {
        $transition = (new Transition('foo', 'bar', function ($arg) {
            $this->assertSame('baz', $arg);

            return true;
        }))->withArguments('baz');

        $this->assertNotSame($transition, $transition->withArguments('baz'));
        $this->assertTrue(($transition->withArguments('baz'))());
    }

    public function testFailingHandlerInvoke()
    {
        $transition = (new Transition('foo', 'bar', function ($arg) {
            $this->assertSame('baz', $arg);

            return false;
        }))->withArguments('baz');

        $this->assertNotSame($transition, $transition->withArguments('baz'));
        $this->assertFalse(($transition->withArguments('baz'))());
    }

    public function testRollbackFromHandlerResult()
    {
        $this->expectOutputString('OK');
        $transition = (new Transition('foo', 'bar', function () {
            return false;
        }, function () {
            echo 'OK';
        }));

        $this->assertFalse($transition->withArguments('foo')());
    }

    public function testRollbackFromHandlerException()
    {
        $this->expectOutputString('OK');
        $transition = (new Transition('foo', 'bar', function () {
            throw new \RuntimeException('ok');
            return false;
        }, function () {
            echo 'OK';
        }));

        $this->assertFalse($transition->withArguments('foo')());
    }
}
