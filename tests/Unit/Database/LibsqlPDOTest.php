<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->pdo = DB::connection()->getPdo();
});

test('it can manage the last insert id value', function () {
    $this->pdo->setLastInsertId(value: 123);

    expect($this->pdo->lastInsertId())->toBe('123');
})->group('LibsqlPDOTest', 'UnitTest');
