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
    private bool $initialized = false;

    /** @var ServiceProviderInterface[] */
    private array $serviceProviders = [];

    /** @var object[] */
    private array $instances = [];
    /** @var Closure[] */
    private array $bindings = [];


    /** @var true[] */
    private array $singleton = [];
    /** @var array[] */
    private array $extend = [];

    use ContainerTrait;

    public function register(ServiceProviderInterface $provider): void
    {
        $this->serviceProviders[] = $provider;
    }

    public function singleton(string $className, string|Closure|FactoryInterface $binding): static
    {
        $this->singleton[$className] = true;
        return $this->bind($className, $binding);
    }

    public function bind(string $id, string|Closure|FactoryInterface $binding): static
    {
        $this->initialized = false;

        if ($binding instanceof FactoryInterface) {
            $binding = $binding->build(...);
        } elseif (is_string($binding)) {
            $binding = fn () => $this->getDelegate()->get($binding);
        }

        $this->bindings[$id] = $binding;

        return $this;
    }

    public function extend(string $className, Closure $decorator): static
    {
        if (!isset($this->extend[$className])) {
            $this->extend[$className][] = $decorator;
        }

        $this->extend[$className][] = $decorator;

        return $this;
    }

    public function get(string $service): mixed
    {
        if (!$this->initialized) {
            $this->load();
        }
        $instance = null;

        if (isset($this->instances[$service])) {
            return $this->instances[$service];
        }

        /** @var Closure|null $factory */
        $instance = ($this->bindings[$service] ?? fn () => parent::get($service))($this);

        if (isset($this->singleton[$service])) {
            $this->instances[$service] = $instance;
        }

        if (isset($this->extend[$service])) {
            foreach ($this->extend[$service] as $decorator) {
                $instance = $decorator($instance, $this);
            }
        }


        assert(
            $instance !== null,
            new UnknownDependencyException("Unable to resolve dependency '$service'"),
        );

        return $this->enforceReturnType($service, $instance);
    }

    public function has(string $id): bool
    {
        return isset($this->bindings[$id]) || parent::has($id);
    }

    public function load(): void
    {
        foreach ($this->serviceProviders as $provider) {
            $provider->register($this);
        }

        foreach ($this->serviceProviders as $provider) {
            if (method_exists($provider, 'boot')) {
                $provider->boot($this);
            }
        }

        $this->initialized = true;
    }
}
