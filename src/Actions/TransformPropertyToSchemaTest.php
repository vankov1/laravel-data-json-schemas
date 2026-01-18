<?php

use BasilLangevin\LaravelDataJsonSchemas\Actions\ApplyAnnotationsToSchema;
use BasilLangevin\LaravelDataJsonSchemas\Actions\ApplyEnumToSchema;
use BasilLangevin\LaravelDataJsonSchemas\Actions\ApplyPropertiesToDataObjectSchema;
use BasilLangevin\LaravelDataJsonSchemas\Actions\ApplyRequiredToDataObjectSchema;
use BasilLangevin\LaravelDataJsonSchemas\Actions\ApplyRuleConfigurationsToSchema;
use BasilLangevin\LaravelDataJsonSchemas\Actions\ApplySchemaTypeOverride;
use BasilLangevin\LaravelDataJsonSchemas\Actions\SetupSchema;
use BasilLangevin\LaravelDataJsonSchemas\Actions\TransformPropertyToSchema;
use BasilLangevin\LaravelDataJsonSchemas\Attributes\SchemaType;
use BasilLangevin\LaravelDataJsonSchemas\Enums\Format;
use BasilLangevin\LaravelDataJsonSchemas\Schemas\ObjectSchema;
use BasilLangevin\LaravelDataJsonSchemas\Schemas\SimpleObjectSchema;
use BasilLangevin\LaravelDataJsonSchemas\Schemas\StringSchema;
use BasilLangevin\LaravelDataJsonSchemas\Schemas\UnionSchema;
use BasilLangevin\LaravelDataJsonSchemas\Support\PropertyWrapper;
use BasilLangevin\LaravelDataJsonSchemas\Support\SchemaTree;
use BasilLangevin\LaravelDataJsonSchemas\Tests\Integration\DataClasses\PersonData;
use BasilLangevin\LaravelDataJsonSchemas\Tests\Support\Enums\TestIntegerEnum;
use BasilLangevin\LaravelDataJsonSchemas\Tests\Support\Enums\TestStringEnum;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Data;

covers(TransformPropertyToSchema::class);

beforeEach(function () {
    $this->tree = app(SchemaTree::class);
});

class PropertyTransformActionTest extends Data
{
    public function __construct(
        public array $arrayProperty,
        public bool $boolProperty,
        public float $floatProperty,
        public int $intProperty,
        public object $objectProperty,
        public string $stringProperty,
        public TestStringEnum $stringEnumProperty,
        public TestIntegerEnum $intEnumProperty,
        public DateTime $testDateTime,
        public DateTimeInterface $testDateTimeInterface,
        public CarbonInterface $testCarbonInterface,
        public Carbon $testCarbon,
        public PersonData $dataObjectProperty,
        public ?PersonData $nullableDataObjectProperty,
    ) {}
}

it('calls the SetupSchema action', function () {
    $property = PropertyWrapper::make(PropertyTransformActionTest::class, 'stringProperty');

    $mock = $this->mock(SetupSchema::class);

    $mock->shouldReceive('handle')->once()
        ->andReturn(StringSchema::make());

    $action = new TransformPropertyToSchema;
    $action->handle($property, $this->tree);
});

it('calls the ApplyEnumToSchema action', function () {
    $property = PropertyWrapper::make(PropertyTransformActionTest::class, 'stringEnumProperty');

    $mock = $this->mock(ApplyEnumToSchema::class);

    $mock->shouldReceive('handle')->once()
        ->andReturn(StringSchema::make());

    $action = new TransformPropertyToSchema;
    $action->handle($property, $this->tree);
});

it('calls the ApplyAnnotationsToSchema action', function () {
    $property = PropertyWrapper::make(PropertyTransformActionTest::class, 'stringProperty');

    $mock = $this->mock(ApplyAnnotationsToSchema::class);

    $mock->shouldReceive('handle')->once()
        ->andReturn(StringSchema::make());

    $action = new TransformPropertyToSchema;
    $action->handle($property, $this->tree);
});

it('calls the ApplyRuleConfigurationsToSchema action', function () {
    $property = PropertyWrapper::make(PropertyTransformActionTest::class, 'stringProperty');

    $mock = $this->mock(ApplyRuleConfigurationsToSchema::class);

    $mock->shouldReceive('handle')->once()
        ->andReturn(StringSchema::make());

    $action = new TransformPropertyToSchema;
    $action->handle($property, $this->tree);
});

it('applies the DateTime format to DateTime properties', function ($property) {
    $property = PropertyWrapper::make(PropertyTransformActionTest::class, $property);

    $action = new TransformPropertyToSchema;
    $schema = $action->handle($property, $this->tree);

    expect($schema->getFormat())->toBe(Format::DateTime);
})->with([
    ['testDateTime'],
    ['testDateTimeInterface'],
    ['testCarbonInterface'],
    ['testCarbon'],
]);

it('calls the ApplyPropertiesToDataObjectSchema action when the property is a data object', function () {
    $property = PropertyWrapper::make(PropertyTransformActionTest::class, 'dataObjectProperty');

    $mock = $this->mock(ApplyPropertiesToDataObjectSchema::class);

    $mock->shouldReceive('handle')->once()
        ->andReturn(ObjectSchema::make());

    $action = new TransformPropertyToSchema;
    $action->handle($property, $this->tree);
});

it('calls the ApplyRequiredToDataObjectSchema action when the property is a data object', function () {
    $property = PropertyWrapper::make(PropertyTransformActionTest::class, 'dataObjectProperty');

    $mock = $this->mock(ApplyRequiredToDataObjectSchema::class);

    $mock->shouldReceive('handle')->once()
        ->andReturn(ObjectSchema::make());

    $action = new TransformPropertyToSchema;
    $action->handle($property, $this->tree);
});

it('returns a UnionSchema for nullable data object properties', function () {
    $property = PropertyWrapper::make(PropertyTransformActionTest::class, 'nullableDataObjectProperty');

    $action = new TransformPropertyToSchema;
    $schema = $action->handle($property, $this->tree);

    expect($schema)->toBeInstanceOf(UnionSchema::class);
});

it('includes null type in anyOf for nullable data objects', function () {
    $property = PropertyWrapper::make(PropertyTransformActionTest::class, 'nullableDataObjectProperty');

    $action = new TransformPropertyToSchema;
    $schema = $action->handle($property, $this->tree);

    $array = $schema->toArray();

    expect($array)->toHaveKey('anyOf')
        ->and($array['anyOf'])->toHaveCount(2);

    // Check that one is object (or $ref to object) and one is null
    $hasObjectOrRef = collect($array['anyOf'])->contains(fn ($item) => isset($item['$ref']) || (isset($item['type']) && $item['type'] === 'object'));
    $hasNull = collect($array['anyOf'])->contains(fn ($item) => isset($item['type']) && $item['type'] === 'null');

    expect($hasObjectOrRef)->toBeTrue()
        ->and($hasNull)->toBeTrue();
});

class SchemaTypeOverrideTest extends Data
{
    public function __construct(
        #[SchemaType('object')]
        public array $headers,

        #[SchemaType('object')]
        public ?array $nullableHeaders,
    ) {}
}

it('calls the ApplySchemaTypeOverride action', function () {
    $property = PropertyWrapper::make(SchemaTypeOverrideTest::class, 'headers');

    $mock = $this->mock(ApplySchemaTypeOverride::class);

    $mock->shouldReceive('handle')->once()
        ->andReturn(SimpleObjectSchema::make()->applyType());

    $action = new TransformPropertyToSchema;
    $action->handle($property, $this->tree);
});

it('transforms array property with SchemaType object to SimpleObjectSchema', function () {
    $property = PropertyWrapper::make(SchemaTypeOverrideTest::class, 'headers');

    $action = new TransformPropertyToSchema;
    $schema = $action->handle($property, $this->tree);

    expect($schema)->toBeInstanceOf(SimpleObjectSchema::class);
    expect($schema->toArray())->toEqual(['type' => 'object']);
});

it('transforms nullable array property with SchemaType object to consolidated union', function () {
    $property = PropertyWrapper::make(SchemaTypeOverrideTest::class, 'nullableHeaders');

    $action = new TransformPropertyToSchema;
    $schema = $action->handle($property, $this->tree);

    expect($schema)->toBeInstanceOf(UnionSchema::class);
    expect($schema->toArray())->toEqual(['type' => ['object', 'null']]);
});
