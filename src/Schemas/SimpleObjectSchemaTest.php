<?php

use BasilLangevin\LaravelDataJsonSchemas\Schemas\SimpleObjectSchema;

covers(SimpleObjectSchema::class);

it('generates a simple object schema')
    ->expect(fn () => SimpleObjectSchema::make()->applyType())
    ->toArray()
    ->toEqual([
        'type' => 'object',
    ]);

it('supports titles')
    ->expect(fn () => SimpleObjectSchema::make()->applyType()->title('Test Title'))
    ->toArray()
    ->toEqual([
        'type' => 'object',
        'title' => 'Test Title',
    ]);

it('supports descriptions')
    ->expect(fn () => SimpleObjectSchema::make()->applyType()->description('Test Description'))
    ->toArray()
    ->toEqual([
        'type' => 'object',
        'description' => 'Test Description',
    ]);

it('supports const values')
    ->expect(fn () => SimpleObjectSchema::make()->applyType()->const(['key' => 'value']))
    ->toArray()
    ->toEqual([
        'type' => 'object',
        'const' => ['key' => 'value'],
    ]);
