<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::create('date_table', function ($table) {
        $table->id();
        $table->date('started_at');
    });
});

afterEach(function () {
    Schema::dropIfExists('date_table');
});

test('it can insert a new date data', function () {
    $result = DB::table('date_table')->insert(['started_at' => $date = '2021-01-01']);
    $data = DB::table('date_table')->first();

    expect($result)->toBeTrue()
        ->and(DB::table('date_table')->count())->toBe(1)
        ->and($data->started_at)->toBe($date);
})->group('DateDataTest', 'DataTypes', 'FeatureTest');

test('it can update an existing date data', function () {
    DB::table('date_table')->insert(['started_at' => '2021-01-01']);

    $result = DB::table('date_table')->update(['started_at' => $date = '2021-02-01']);
    $data = DB::table('date_table')->first();

    expect($result)->toBe(1)
        ->and($data->started_at)->toBe($date);
})->group('DateDataTest', 'DataTypes', 'FeatureTest');

test('it can find the saved record', function () {
    DB::table('date_table')->insert(['started_at' => '2021-02-01']);
    DB::table('date_table')->insert(['started_at' => $date = '2021-01-01']);

    $found = DB::table('date_table')->where('started_at', $date)->first();

    expect($found->id)->toBe(2)
        ->and($found->started_at)->toBe($date);
})->group('DateDataTest', 'DataTypes', 'FeatureTest');
