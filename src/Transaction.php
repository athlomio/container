<?php
declare(strict_types=1);

namespace Archer\Container;

use Archer\Container\Contract\Registry;
use Archer\Container\Exception\TransactionException;

final class Transaction implements Registry
{
    public protected(set) bool $active = false;
    
    protected array $instances = [];
    protected array $transients = [];
    protected array $singletons = [];

    public function start(): self 
    {
        if ($this->active) {
            throw TransactionException::transactionAlreadyActive();
        }

        $this->active = true;
        return $this;
    }

    public function end(): self
    {
        $this->assertActive();
        $this->clean();

        $this->active = false;
        return $this;
    }

    public function get(string $id): mixed
    {
        $this->assertActive();
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

        throw TransactionException::serviceUnavailable($id);
    }

    public function has(string $id): bool
    {
        if (! $this->active) {
            return false;
        }

        return array_key_exists($id, $this->instances)
            || array_key_exists($id, $this->singletons)
            || array_key_exists($id, $this->transients);
    }

    public function transient(string $id, callable $factory): self
    {
        $this->assertActive();
        $this->transients[$id] = $factory;
        return $this;
    }

    public function singleton(string $id, callable $factory): self
    {
        $this->assertActive();
        $this->singletons[$id] = $factory;
        return $this;
    }

    public function instance(string $id, mixed $instance): self
    {
        $this->assertActive();
        $this->instances[$id] = $instance;
        return $this;
    }

    protected function clean(): void
    {
        $this->instances = [];
        $this->transients = [];
        $this->singletons = [];
    }

    protected function assertActive(): void
    {
        if ($this->active) {
            return;
        }
        
        throw TransactionException::noActiveTransaction();
    }
}