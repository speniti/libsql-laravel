<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Libsql\Laravel\Tests\Feature\EloquentAttributeCasting\CastingModels\StringableCastingModel;

beforeEach(function () {
    Schema::create('stringable_casting_table', function ($table) {
        $table->id();
        $table->string('data');
    });
});

afterEach(function () {
    Schema::dropIfExists('stringable_casting_table');
});

test('it can insert a new record using Eloquent ORM', function () {
    $data = 'John Doe';

    StringableCastingModel::create([
        'data' => $data,
    ]);

    $result = StringableCastingModel::first();

    expect($result->id)->toBe(1)
        ->and(get_class($result->data))->toBe('Illuminate\Support\Stringable')
        ->and($result->data->toString())->toBe($data);
})->group('AsStringableCastingTest', 'EloquentAttributeCastings', 'FeatureTest');

test('it can update an existing record using Eloquent ORM', function () {
    $data = 'John Doe';

    StringableCastingModel::create([
        'data' => $data,
    ]);

    $newData = 'Jane Doe';

    StringableCastingModel::first()->update([
        'data' => $newData,
    ]);

    $result = StringableCastingModel::first();

    expect($result->id)->toBe(1)
        ->and(get_class($result->data))->toBe('Illuminate\Support\Stringable')
        ->and($result->data->toString())->toBe($newData);
});

test('it can find the saved record', function () {
    $data = 'John Doe';

    StringableCastingModel::create([
        'data' => 'Jane Doe',
    ]);
    StringableCastingModel::create([
        'data' => $data,
    ]);

    $found = StringableCastingModel::where('data', $data)->first();

    expect($found->data->toString())->toBe($data);
})->group('AsStringableCastingTest', 'EloquentAttributeCastings', 'FeatureTest');
