<?php

namespace BasilLangevin\LaravelDataJsonSchemas\Actions;

use BasilLangevin\LaravelDataJsonSchemas\Actions\Concerns\Runnable;
use BasilLangevin\LaravelDataJsonSchemas\Attributes\SchemaType;
use BasilLangevin\LaravelDataJsonSchemas\Schemas\ArraySchema;
use BasilLangevin\LaravelDataJsonSchemas\Schemas\Contracts\Schema;
use BasilLangevin\LaravelDataJsonSchemas\Schemas\Contracts\SingleTypeSchema;
use BasilLangevin\LaravelDataJsonSchemas\Schemas\SimpleObjectSchema;
use BasilLangevin\LaravelDataJsonSchemas\Schemas\UnionSchema;
use BasilLangevin\LaravelDataJsonSchemas\Support\PropertyWrapper;

class ApplySchemaTypeOverride
{
    /** @use Runnable<array{Schema, PropertyWrapper}, Schema> */
    use Runnable;

    public function handle(Schema $schema, PropertyWrapper $property): Schema
    {
        $attribute = $property->getAttribute(SchemaType::class);

        if (is_null($attribute)) {
            return $schema;
        }

        $overrideType = $attribute->getValue();

        if ($overrideType !== 'object') {
            return $schema;
        }

        // For UnionSchema (nullable types), replace ArraySchema with SimpleObjectSchema
        if ($schema instanceof UnionSchema) {
            $schemas = $schema->getConstituentSchemas()->map(function (SingleTypeSchema $s) {
                if ($s instanceof ArraySchema) {
                    // Don't call applyType() here - let buildConstituentSchemasFromSchemas
                    // handle it based on union consolidatability
                    return SimpleObjectSchema::make();
                }

                return $s;
            });

            return UnionSchema::make()->buildConstituentSchemasFromSchemas($schemas);
        }

        // For direct ArraySchema, replace with SimpleObjectSchema
        if ($schema instanceof ArraySchema) {
            return SimpleObjectSchema::make()->applyType();
        }

        return $schema;
    }
}
