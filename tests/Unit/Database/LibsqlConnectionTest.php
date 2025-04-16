<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

test('it can enable query logging feature', function () {
    DB::connection('memory')->enableQueryLog();

    expect(DB::connection('memory')->logging())->toBeTrue();
})->group('LibsqlConnectionTest', 'UnitTest');

test('it can disable query logging feature', function () {
    DB::connection('memory')->disableQueryLog();

    expect(DB::connection('memory')->logging())->toBeFalse();
})->group('LibsqlConnectionTest', 'UnitTest');

test('it can get the query log', function () {
    DB::connection('memory')->enableQueryLog();

    $log = DB::connection('memory')->getQueryLog();

    expect($log)->toBeArray()
        ->and($log)->toHaveCount(0);
})->group('LibsqlConnectionTest', 'UnitTest');

test('it can flush the query log', function () {
    DB::connection('memory')->enableQueryLog();

    DB::connection('memory')->flushQueryLog();

    $log = DB::connection('memory')->getQueryLog();

    expect($log)->toBeArray()
        ->and($log)->toHaveCount(0);
})->group('LibsqlConnectionTest', 'UnitTest');
