<?php
declare(strict_types=1);

namespace Archer\Container;

use ReflectionClass;
use ReflectionParameter;
use ReflectionAttribute;

use Archer\Container\Attribute\Inject;
use Archer\Container\Contract\Registry;
use Archer\Container\Exception\AutowireException;

class Container implements Registry
{
    protected readonly Transaction $transaction;

    protected array $instances = [];
    protected array $transients = [];
    protected array $singletons = [];

    public function __construct()
    {
        $this->transaction = new Transaction();
    }

    public function get(string $id): mixed
    {
        if ($this->transaction->active and $this->transaction->has($id)) {
            return $this->transaction->get($id);
        }

        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }

        if (array_key_exists($id, $this->singletons)) {
            $this->instances[$id] = $this->singletons[$id]();
            unset($this->singletons[$id]);

            return $this->instances[$id];
        }

        if (array_key_exists($id, $this->transients)) {
            return $this->transients[$id]();
        }

        $this->autowire($id);
        return $this->get($id);
    }

    public function has(string $id): bool
    {
        if ($this->transaction->active and $this->transaction->has($id)) {
            return true;
        }

        return array_key_exists($id, $this->instances)
            || array_key_exists($id, $this->singletons)
            || array_key_exists($id, $this->transients);
    }

    public function start_transaction(): self
    {
        $this->transaction->start();
        return $this;
    }

    public function end_transaction(): self
    {
        $this->transaction->end();
        return $this;
    }

    public function transient(string $id, callable $factory): self
    {
        if ($this->transaction->active) {
            $this->transaction->transient($id, $factory);
            return $this;
        }

        $this->transients[$id] = $factory;
        return $this;
    }

    public function singleton(string $id, callable $factory): self
    {
        if ($this->transaction->active) {
            $this->transaction->singleton($id, $factory);
            return $this;
        }

        $this->singletons[$id] = $factory;
        return $this;
    }

    public function instance(string $id, mixed $instance): self
    {
        if ($this->transaction->active) {
            $this->transaction->instance($id, $instance);
            return $this;
        }

        $this->instances[$id] = $instance;
        return $this;
    }

    protected function autowire(string $id): self
    {
        if (! class_exists($id)) {
            throw AutowireException::invalidFullyQualifiedClassName($id);
        }

        $class = new ReflectionClass($id);
        if (! $class->isInstantiable()) {
            throw AutowireException::serviceNotInstantiable($id);
        }

        $constructor = $class->getConstructor();
        if ($constructor === null or count($parameters = $constructor->getParameters()) === 0) {
            return $this->transient($id, fn () => new $id());
        }

        $this->transient($id, function () use ($id, $parameters) {
            $resolved = [];
            foreach ($parameters as $parameter) {
                $resolved[] = $this->resolveParameter($parameter);
            }

            return new $id(...$resolved);
        });

        return $this;
    }

    protected function resolveParameter(ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();
        if ($type === null) {
            throw AutowireException::parameterHasNoType($parameter->getName());
        }

        if (! $type->isBuiltin()) {
            return $this->get($type->getName());
        }

        $attributes = $parameter->getAttributes(Inject::class, ReflectionAttribute::IS_INSTANCEOF);
        if (count($attributes) > 1) {
            throw AutowireException::onlyOneInjectAttributeAllowed($parameter->getName());
        }

        if (count($attributes) === 1) {
            return $this->get($attributes[0]->newInstance()->id);
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        if ($type->allowsNull()) {
            return null;
        }

        throw AutowireException::unresolvableParameter($parameter->getName());
    }
}