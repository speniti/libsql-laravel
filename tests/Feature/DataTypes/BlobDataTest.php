<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::create('blob_table', function ($table) {
        $table->id();
        $table->binary('blob');
    });
});

afterEach(function () {
    Schema::dropIfExists('blob_table');
});

test('it can insert a new blob data', function () {
    $result = DB::table('blob_table')->insert(['blob' => $bytes = random_bytes(50)]);
    $data = DB::table('blob_table')->first();

    expect($result)->toBeTrue()
        ->and(DB::table('blob_table')->count())->toBe(1)
        ->and($data->blob)->toBe($bytes);
})->group('BlobDataTest', 'DataTypes', 'FeatureTest');

test('it can update an existing blob data', function () {
    DB::table('blob_table')->insert(['blob' => random_bytes(50)]);

    $result = DB::table('blob_table')->update(['blob' => $bytes = random_bytes(50)]);
    $data = DB::table('blob_table')->first();

    expect($result)->toBe(1)
        ->and($data->blob)->toBe($bytes);
})->group('BlobDataTest', 'DataTypes', 'FeatureTest');
