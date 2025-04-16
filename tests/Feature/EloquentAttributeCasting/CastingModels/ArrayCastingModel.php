<?php

declare(strict_types=1);

namespace Libsql\Laravel\Tests\Feature\EloquentAttributeCasting\CastingModels;

use Illuminate\Database\Eloquent\Model;

class ArrayCastingModel extends Model
{
    public $timestamps = false;

    protected $guarded = false;

    protected $table = 'array_casting_table';

    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }
}
