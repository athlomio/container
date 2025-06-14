<?php
declare(strict_types=1);

namespace Archer\Container\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
final readonly class Inject
{
    public function __construct(
        public string $id
    ) {}    
}