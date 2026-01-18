<?php

namespace BasilLangevin\LaravelDataJsonSchemas\Actions;

use BasilLangevin\LaravelDataJsonSchemas\Actions\Concerns\Runnable;
use BasilLangevin\LaravelDataJsonSchemas\Schemas\ArraySchema;
use BasilLangevin\LaravelDataJsonSchemas\Schemas\Contracts\Schema;
use BasilLangevin\LaravelDataJsonSchemas\Schemas\NullSchema;
use BasilLangevin\LaravelDataJsonSchemas\Schemas\StringSchema;
use BasilLangevin\LaravelDataJsonSchemas\Schemas\UnionSchema;
use BasilLangevin\LaravelDataJsonSchemas\Support\PropertyWrapper;
use BasilLangevin\LaravelDataJsonSchemas\Support\SchemaTree;

class TransformPropertyToSchema
{
    /** @use Runnable<array{PropertyWrapper, SchemaTree}, Schema> */
    use Runnable;

    /**
     * Transform a property to a Schema with the appropriate keywords.
     */
    public function handle(PropertyWrapper $property, SchemaTree $tree): Schema
    {
        if ($property->isDataObject()) {
            $schema = TransformDataClassToSchema::run($property->getDataClassName(), $tree);

            if ($property->isNullable()) {
                return UnionSchema::make()
                    ->buildConstituentSchemasFromSchemas(collect([$schema, NullSchema::make()]));
            }

            return $schema;
        }

        return MakeSchemaForReflectionType::run($property->getReflectionType())
            ->pipe(fn (Schema $schema) => SetupSchema::run($schema, $property, $tree))
            ->pipe(fn (Schema $schema) => ApplySchemaTypeOverride::run($schema, $property))
            ->pipe(fn (Schema $schema) => ApplyAnnotationsToSchema::run($schema, $property))
            ->when($property->isEnum(), fn (Schema $schema) => ApplyEnumToSchema::run($schema, $property))
            ->when($property->isDateTime(), fn (StringSchema|UnionSchema $schema) => ApplyDateTimeFormatToSchema::run($schema))
            ->pipe(fn (Schema $schema) => $this->shouldApplyArrayItems($schema, $property)
                ? ApplyArrayItemsToSchema::run($schema, $property, $tree)
                : $schema)
            ->pipe(fn (Schema $schema) => ApplyRuleConfigurationsToSchema::run($schema, $property))
            ->tree($tree);
    }

    /**
     * Determine if array items should be applied to the schema.
     */
    protected function shouldApplyArrayItems(Schema $schema, PropertyWrapper $property): bool
    {
        if (! $property->isArray()) {
            return false;
        }

        if ($schema instanceof ArraySchema) {
            return true;
        }

        if ($schema instanceof UnionSchema) {
            return $schema->getConstituentSchemas()
                ->contains(fn ($s) => $s instanceof ArraySchema);
        }

        return false;
    }
}
