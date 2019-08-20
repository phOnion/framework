<?php declare(strict_types=1);
namespace Onion\Framework\State\Factory;

use Onion\Framework\Common\Config\Container;
use Onion\Framework\Dependency\Interfaces\FactoryBuilderInterface;
use Onion\Framework\Dependency\Interfaces\FactoryInterface;
use Onion\Framework\State\Flow;
use Onion\Framework\State\Transition;
use Psr\Container\ContainerInterface;

class FlowFactory implements FactoryBuilderInterface
{
    public function build(ContainerInterface $container, string $key): FactoryInterface
    {
        return new class($key, $container->get("states.{$key}")) implements FactoryInterface {
            private $name;
            private $states;

            public function __construct(string $name, Container $states)
            {
                $this->name = $name;
                $this->states = $states;
            }

            public function build(\Psr\Container\ContainerInterface $container)
            {
                $flow = new Flow($this->name, $this->states->get('initial'));

                foreach ($this->states->get('transitions') as $def) {
                    $flow->addTransition(new Transition($def['source'], $def['destination'], $def['handler'] ?? null));
                }

                return $flow;
            }
        };
    }
}
