<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Libsql\Laravel\Tests\Feature\EloquentAttributeCasting\CastingModels\CollectionCastingModel;

beforeEach(function () {
    Schema::create('collection_casting_table', function ($table) {
        $table->id();
        $table->json('data');
    });
});

afterEach(function () {
    Schema::dropIfExists('collection_casting_table');
});

test('it can insert a new record using Eloquent ORM', function () {
    $data = collect(['name' => 'John Doe', 'city' => 'New York']);

    CollectionCastingModel::create([
        'data' => $data,
    ]);

    $result = CollectionCastingModel::first();

    expect($result->id)->toBe(1)
        ->and(get_class($result->data))->toBe('Illuminate\Support\Collection')
        ->and($result->data->toArray())->toBe($data->toArray())
        ->and($result->data->get('name'))->toBe('John Doe')
        ->and($result->data->get('city'))->toBe('New York');
})->group('CollectionCastingTest', 'EloquentAttributeCastings', 'FeatureTest');

test('it can update an existing record using Eloquent ORM', function () {
    $data = collect(['name' => 'John Doe', 'city' => 'New York']);

    CollectionCastingModel::create([
        'data' => $data,
    ]);

    $newData = collect(['name' => 'Jane Doe', 'city' => 'Los Angeles']);

    CollectionCastingModel::first()->update([
        'data' => $newData,
    ]);

    $result = CollectionCastingModel::first();

    expect($result->id)->toBe(1)
        ->and(get_class($result->data))->toBe('Illuminate\Support\Collection')
        ->and($result->data->toArray())->toBe($newData->toArray())
        ->and($result->data->get('name'))->toBe('Jane Doe')
        ->and($result->data->get('city'))->toBe('Los Angeles');
})->group('CollectionCastingTest', 'EloquentAttributeCastings', 'FeatureTest');
