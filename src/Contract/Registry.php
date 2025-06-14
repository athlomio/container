<?php
declare(strict_types=1);

namespace Archer\Container\Contract;

interface Registry
{
    public function get(string $id): mixed;
    public function has(string $id): bool;

    public function transient(string $id, callable $factory): self;
    public function singleton(string $id, callable $factory): self;
    public function instance(string $id, mixed $instance): self;
}