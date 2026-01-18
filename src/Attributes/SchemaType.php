<?php

namespace BasilLangevin\LaravelDataJsonSchemas\Attributes;

use Attribute;
use BasilLangevin\LaravelDataJsonSchemas\Attributes\Contracts\StringAttribute;

/**
 * Overrides the generated JSON Schema type for a property.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class SchemaType implements StringAttribute
{
    public function __construct(
        protected string $type
    ) {}

    public function getValue(): string
    {
        return $this->type;
    }
}
