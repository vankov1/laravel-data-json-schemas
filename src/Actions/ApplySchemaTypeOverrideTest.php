<?php

use BasilLangevin\LaravelDataJsonSchemas\Actions\ApplySchemaTypeOverride;
use BasilLangevin\LaravelDataJsonSchemas\Attributes\SchemaType;
use BasilLangevin\LaravelDataJsonSchemas\Schemas\ArraySchema;
use BasilLangevin\LaravelDataJsonSchemas\Schemas\NullSchema;
use BasilLangevin\LaravelDataJsonSchemas\Schemas\SimpleObjectSchema;
use BasilLangevin\LaravelDataJsonSchemas\Schemas\StringSchema;
use BasilLangevin\LaravelDataJsonSchemas\Schemas\UnionSchema;
use BasilLangevin\LaravelDataJsonSchemas\Support\PropertyWrapper;
use BasilLangevin\LaravelDataJsonSchemas\Tests\TestsSchemaTransformation;
use Spatie\LaravelData\Data;

covers(ApplySchemaTypeOverride::class);

uses(TestsSchemaTransformation::class);

it('returns the schema unchanged when no SchemaType attribute is present', function () {
    $this->class->addArrayProperty('property');
    $property = $this->class->getClassProperty('property');

    $schema = ArraySchema::make()->applyType();

    $action = new ApplySchemaTypeOverride;
    $result = $action->handle($schema, $property);

    expect($result)->toBe($schema);
});

it('replaces ArraySchema with SimpleObjectSchema when SchemaType is object', function () {
    $this->class->addArrayProperty('property', [SchemaType::class => 'object']);
    $property = $this->class->getClassProperty('property');

    $schema = ArraySchema::make()->applyType();

    $action = new ApplySchemaTypeOverride;
    $result = $action->handle($schema, $property);

    expect($result)->toBeInstanceOf(SimpleObjectSchema::class);
    expect($result->toArray())->toEqual(['type' => 'object']);
});

it('replaces ArraySchema in UnionSchema with SimpleObjectSchema when SchemaType is object', function () {
    class TestSchemaTypeOverrideUnionClass extends Data
    {
        public function __construct(
            #[SchemaType('object')]
            public ?array $headers,
        ) {}
    }

    $property = PropertyWrapper::make(TestSchemaTypeOverrideUnionClass::class, 'headers');

    // In the real flow, SetupSchema creates schemas without applyType() for consolidatable unions
    // buildConstituentSchemasFromSchemas will decide whether to call applyType() based on consolidatability
    $unionSchema = UnionSchema::make()
        ->buildConstituentSchemasFromSchemas(collect([
            ArraySchema::make(),
            NullSchema::make(),
        ]));

    $action = new ApplySchemaTypeOverride;
    $result = $action->handle($unionSchema, $property);

    expect($result)->toBeInstanceOf(UnionSchema::class);
    expect($result->toArray())->toEqual(['type' => ['object', 'null']]);
});

it('returns the schema unchanged for non-object override types', function () {
    class TestSchemaTypeOverrideNonObjectClass extends Data
    {
        public function __construct(
            #[SchemaType('string')]
            public array $property,
        ) {}
    }

    $property = PropertyWrapper::make(TestSchemaTypeOverrideNonObjectClass::class, 'property');

    $schema = ArraySchema::make()->applyType();

    $action = new ApplySchemaTypeOverride;
    $result = $action->handle($schema, $property);

    expect($result)->toBe($schema);
});

it('returns a non-array schema unchanged even with SchemaType attribute', function () {
    class TestSchemaTypeOverrideStringClass extends Data
    {
        public function __construct(
            #[SchemaType('object')]
            public string $property,
        ) {}
    }

    $property = PropertyWrapper::make(TestSchemaTypeOverrideStringClass::class, 'property');

    $schema = StringSchema::make()->applyType();

    $action = new ApplySchemaTypeOverride;
    $result = $action->handle($schema, $property);

    expect($result)->toBe($schema);
});
