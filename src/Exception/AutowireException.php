<?php
declare(strict_types=1);

namespace Archer\Container\Exception;

use Exception;

final class AutowireException extends Exception
{
    public static function invalidFullyQualifiedClassName(string $id): self
    {
        return new self("The identifier `{$id}` is not a valid fully qualified class name.");
    }

    public static function serviceNotInstantiable(string $id): self
    {
        return new self("The service `{$id}` cannot be instantiated. Check if the class is abstract, an interface, or missing a public constructor.");
    }

    public static function parameterHasNoType(string $parameter): self
    {
        return new self("The parameter `{$parameter}` has no type declaration and cannot be resolved automatically.");
    }

    public static function onlyOneInjectAttributeAllowed(string $parameter): self
    {
        return new self("Parameter `{$parameter}` can have only one `#[Inject]` attribute.");
    }

    public static function unresolvableParameter(string $parameter): self
    {
        return new self("Cannot resolve the parameter `{$parameter}`. Please provide a value or ensure it can be autowired.");
    }
}