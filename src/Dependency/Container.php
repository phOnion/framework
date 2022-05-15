<?php

declare(strict_types=1);

namespace Onion\Framework\Dependency;

use Closure;
use Onion\Framework\Dependency\Exception\UnknownDependencyException;
use Onion\Framework\Dependency\Interfaces\{ContainerInterface, FactoryInterface, ServiceProviderInterface};
use Onion\Framework\Dependency\ReflectionContainer;
use Onion\Framework\Dependency\Traits\ContainerTrait;

class Container extends ReflectionContainer implements ContainerInterface
{
    use ContainerTrait;

    private bool $initialized = false;

    private array $serviceProviders = [];

    private array $aliases = [];
    private array $instances = [];
    private array $bindings = [];

    private array $singleton = [];
    private array $extend = [];

    public function register(ServiceProviderInterface $provider): void
    {
        $this->serviceProviders[] = $provider;
    }

    public function singleton(string $service, string|object $binding): static
    {
        $this->singleton[$service] = true;

        if (is_string($binding) || $binding instanceof FactoryInterface || $binding instanceof Closure) {
            $this->bind($service, $binding);
        } else {
            $this->instances[$service] = $binding;
        }

        return $this;
    }

    public function bind(string $service, string|Closure|FactoryInterface $binding): static
    {
        if ($binding instanceof FactoryInterface) {
            $binding = $binding->build(...);
        } elseif (is_string($binding)) {
            $binding = fn () => parent::get($binding);
        }

        if ($binding instanceof Closure) {
            $this->bindings[$service] = $binding;
        }

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

    public function get(string $id): mixed
    {
        if ($this->serviceProviders) {
            foreach ($this->serviceProviders as $provider) {
                $provider->register($this);
            }

            foreach ($this->serviceProviders as $provider) {
                if (method_exists($provider, 'boot')) {
                    $provider->boot($this);
                }
            }

            $this->serviceProviders = [];
        }

        $instance = null;
        $service = $this->aliases[$id] ?? $id;

        if (isset($this->instances[$service])) {
            return $this->instances[$service];
        }

        /** @var Closure|null $factory */
        $instance = ($this->bindings[$service] ?? fn () => parent::get($service))($this, $id);

        if (isset($this->extend[$id])) {
            foreach ($this->extend[$id] as $decorator) {
                $instance = $decorator($instance, $this, $id);
            }
        }

        if (isset($this->singleton[$service])) {
            $this->instances[$service] = $instance;
        }

        assert(
            $instance !== null,
            new UnknownDependencyException("Unable to resolve dependency '$id'"),
        );

        return $this->enforceReturnType($id, $instance);
    }

    public function has(string $id): bool
    {
        $service = $this->aliases[$id] ?? $id;

        return isset($this->bindings[$service]) || parent::has($service);
    }
}
