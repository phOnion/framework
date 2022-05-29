<?php

declare(strict_types=1);

namespace Onion\Framework\Dependency;

use Closure;
use InvalidArgumentException;
use LogicException;
use Onion\Framework\Dependency\Exception\ContainerErrorException;
use Onion\Framework\Dependency\Exception\UnknownDependencyException;
use Onion\Framework\Dependency\Interfaces\{
    BootableServiceProviderInterface,
    ContainerInterface,
    FactoryInterface,
    ServiceProviderInterface
};
use Onion\Framework\Dependency\ReflectionContainer;
use Onion\Framework\Dependency\Traits\ContainerTrait;

use function Onion\Framework\generator;

class Container extends ReflectionContainer implements ContainerInterface
{
    use ContainerTrait;

    private bool $allowBindingOverwrite = false;

    private array $serviceProviders = [];

    private array $aliases = [];
    private array $instances = [];
    private array $bindings = [];

    private array $singleton = [];
    private array $extend = [];

    private array $taggedGroups = [];

    public function register(ServiceProviderInterface $provider): void
    {
        $this->serviceProviders[] = $provider;
    }

    public function singleton(string $service, string|object $binding, array $tags = []): static
    {
        $this->singleton[$service] = $service;

        if (\is_string($binding) || $binding instanceof FactoryInterface || $binding instanceof Closure) {
            $this->bind($service, $binding);
        } else {
            $this->instances[$service] = $binding;
        }

        $this->tag($service, ...$tags);

        return $this;
    }

    public function unbind(string $service): void
    {
        \assert(
            $this->allowBindingOverwrite,
            new LogicException('Removing dependencies during non-loading phase should not be done'),
        );

        \assert(
            isset($this->singleton[$service]) || isset($this->bindings[$service]),
            new InvalidArgumentException("Attempting to unbind an unbound dependency"),
        );

        if (isset($this->singleton[$service])) {
            unset($this->singleton[$service]);

            if (isset($this->instances[$service])) {
                unset($this->instances[$service]);
            }
        }

        if (isset($this->bindings[$service])) {
            unset($this->bindings[$service]);
        }

        if (isset($this->extend[$service])) {
            unset($this->extend[$service]);
        }
    }

    public function bind(string $service, string|Closure|FactoryInterface $binding, array $tags = []): static
    {
        \assert(
            !isset($this->bindings[$service]),
            new ContainerErrorException(
                "Unable to overwrite an existing service '{$service}', maybe 'unbind' it first?"
            ),
        );

        if ($binding instanceof FactoryInterface) {
            $binding = $binding->build(...);
        } elseif (\is_string($binding)) {
            $binding = fn () => parent::get($binding);
        }

        $this->bindings[$service] = $binding;

        $this->tag($service, ...$tags);

        return $this;
    }

    public function alias(string $alias, string $service): static
    {
        $this->aliases[$alias] = $service;

        return $this;
    }

    public function extend(string $service, Closure $decorator): static
    {
        if (!isset($this->extend[$service])) {
            $this->extend[$service] = [];
        }

        $this->extend[$service][] = $decorator;

        return $this;
    }

    public function tag(string $service, string ...$tags): void
    {
        foreach ($tags as $tag) {
            if (!isset($this->taggedGroups[$tag])) {
                $this->taggedGroups[$tag] = [];
            }

            $this->taggedGroups[$tag][] = $service;
        }
    }

    public function tagged(string $name): iterable
    {
        $group = $this->taggedGroups[$name] ?? [];
        $resolver = $this->get(...);

        return generator(static function () use ($resolver, $group) {
            foreach ($group as $item) {
                yield $resolver($item);
            }
        });
    }

    public function get(string $id): mixed
    {
        $this->loadProviders();

        $instance = null;
        $service = $this->aliases[$id] ?? $id;

        if (isset($this->instances[$service])) {
            return $this->instances[$service];
        }

        /** @var Closure|null $factory */
        $instance = ($this->bindings[$service] ??
            fn (self $container, string $id) => $this->getDelegate()->get($service))($this, $id);

        if ($instance === null && $this->getDelegate()->has($service)) {
            $instance = $this->getDelegate()->get($service);
        }

        if (isset($this->extend[$id])) {
            foreach ($this->extend[$id] as $decorator) {
                $instance = $decorator($instance, $this, $id);
            }
        }

        if (isset($this->singleton[$service])) {
            $this->instances[$service] = $instance;
        }

        \assert(
            $instance !== null,
            new UnknownDependencyException("Unable to resolve dependency '$id'"),
        );

        return $instance;
    }

    public function has(string $id): bool
    {
        $this->loadProviders();
        $service = $this->aliases[$id] ?? $id;

        return isset($this->bindings[$service]) || parent::has($service);
    }

    private function loadProviders(): void
    {
        if ($this->serviceProviders) {
            $providers = $this->serviceProviders;
            $this->serviceProviders = [];
            $this->allowBindingOverwrite = true;
            foreach ($providers as $provider) {
                $provider->register($this);
            }

            foreach ($providers as $provider) {
                if ($provider instanceof BootableServiceProviderInterface) {
                    $provider->boot($this);
                }
            }


            $this->allowBindingOverwrite = false;
        }
    }
}
