<?php

use BasilLangevin\LaravelDataJsonSchemas\Attributes\SchemaType;

it('can be instantiated')
    ->expect(new SchemaType('object'))
    ->toBeInstanceOf(SchemaType::class);

it('can get its value')
    ->expect(fn () => new SchemaType('object'))
    ->getValue()
    ->toBe('object');
