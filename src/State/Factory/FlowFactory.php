<?php

declare(strict_types=1);

namespace Onion\Framework\State\Factory;

use Closure;
use Onion\Framework\Dependency\Interfaces\FactoryBuilderInterface;
use Onion\Framework\State\Flow;
use Onion\Framework\State\Interfaces\HistoryInterface;
use Psr\Container\ContainerInterface;

class FlowFactory implements FactoryBuilderInterface
{
    public function build(ContainerInterface $container, string $key): Closure
    {
        return function (ContainerInterface $container) use ($key) {
            $flow = new Flow(
                $key,
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
        };
    }
}
