<?php

declare(strict_types=1);

namespace Onion\Framework\Dependency;

use Onion\Framework\Common\Dependency\Traits\ContainerTrait;
use Onion\Framework\Common\Dependency\Traits\DelegateContainerTrait;
use Onion\Framework\Dependency\Exception\ContainerErrorException;
use Onion\Framework\Dependency\Exception\UnknownDependency;
use Onion\Framework\Dependency\Interfaces\DelegateContainerInterface;
use Psr\Container\ContainerInterface;

use function Onion\Framework\Common\merge;

class ProxyContainer implements ContainerInterface, DelegateContainerInterface, \Countable
{
    use ContainerTrait;
    use DelegateContainerTrait;

    public function get($id): mixed
    {
        if (count($this) === 0) {
            throw new UnknownDependency("No containers provided, can't retrieve '{$id}'");
        }

        $resolvers = [];
        foreach ($this->getAttachedContainers() as $container) {
            if ($container->has($id)) {
                $resolvers[] = $container;
            }
        }

        $result = null;
        if (empty($resolvers)) {
            throw new UnknownDependency("Unable to resolve '{$id}'");
        }

        foreach ($resolvers as $resolver) {
            try {
                $r = $resolver->get($id);
                if (!is_array($r)) {
                    return $r;
                }

                $result = merge(($result ?? []), $r);
            } catch (ContainerErrorException $ex) {
                continue;
            }
        }

        if ($result === null) {
            throw new ContainerErrorException("Unable to resolve '{$id}'");
        }

        return $result;
    }
}
