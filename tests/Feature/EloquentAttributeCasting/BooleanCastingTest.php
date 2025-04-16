<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Libsql\Laravel\Tests\Feature\EloquentAttributeCasting\CastingModels\BooleanCastingModel;

beforeEach(function () {
    Schema::create('boolean_casting_table', function ($table) {
        $table->id();
        $table->boolean('confirmed');
    });
});

afterEach(function () {
    Schema::dropIfExists('boolean_casting_table');
});

test('it can insert a new record using Eloquent ORM', function () {
    $confirmed = true;

    BooleanCastingModel::create([
        'confirmed' => $confirmed,
    ]);

    $result = BooleanCastingModel::first();

    expect($result->id)->toBe(1)
        ->and(gettype($result->confirmed))->toBe('boolean')
        ->and($result->confirmed)->toBe($confirmed);
})->group('BooleanCastingTest', 'EloquentAttributeCastings', 'FeatureTest');

test('it can update an existing record using Eloquent ORM', function () {
    $confirmed = true;

    BooleanCastingModel::create([
        'confirmed' => $confirmed,
    ]);

    $newConfirmed = false;

    BooleanCastingModel::first()->update([
        'confirmed' => $newConfirmed,
    ]);

    $result = BooleanCastingModel::first();

    expect($result->id)->toBe(1)
        ->and(gettype($result->confirmed))->toBe('boolean')
        ->and($result->confirmed)->toBe($newConfirmed);
})->group('BooleanCastingTest', 'EloquentAttributeCastings', 'FeatureTest');

test('it can find the saved record using Eloquent ORM', function () {
    $confirmed = true;

    BooleanCastingModel::create([
        'confirmed' => false,
    ]);
    BooleanCastingModel::create([
        'confirmed' => $confirmed,
    ]);

    $found = BooleanCastingModel::where('confirmed', $confirmed)->first();

    expect($found->id)->toBe(2)
        ->and(gettype($found->confirmed))->toBe('boolean')
        ->and($found->confirmed)->toBe($confirmed);
})->group('BooleanCastingTest', 'EloquentAttributeCastings', 'FeatureTest');
