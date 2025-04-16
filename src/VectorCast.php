<?php

declare(strict_types=1);

namespace Libsql\Laravel;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Facades\DB;
use JsonException;

/** @implements CastsAttributes<string|false,list<string>|null> */
class VectorCast implements CastsAttributes
{
    /** @throws JsonException */
    public function get($model, $key, $value, $attributes): string|false
    {
        return json_encode($value, JSON_THROW_ON_ERROR);
    }

    public function set($model, $key, $value, $attributes)
    {
        return DB::raw(sprintf("vector32('[%s]')", implode(',', $value)));
    }
}
