<?php

declare(strict_types=1);

namespace Onion\Framework\State\Factory;

use Closure;
use Onion\Framework\Dependency\Interfaces\ContextFactoryInterface;
use Onion\Framework\Dependency\Interfaces\FactoryBuilderInterface;
use Onion\Framework\State\Flow;
use Onion\Framework\State\Interfaces\FlowInterface;
use Psr\Container\ContainerInterface;

class FlowFactory implements ContextFactoryInterface
{
    public function build(ContainerInterface $container, string $key = null): FlowInterface
    {
        $flow = new Flow(
            (string) $key,
            $container->get("workflows.{$key}.initial"),
            $container->has("workflows.{$key}.history") ?
                $container->get($container->get("workflows.{$key}.history")) :
                null,
        );

        foreach ($container->get("workflows.{$key}.states") as $state) {
            $flow->addTransition(
                $state['from'],
                $state['to'],
                $state['handler']
            );
        }

        return $flow;
    }
}
