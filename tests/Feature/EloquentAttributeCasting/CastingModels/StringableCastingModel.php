<?php

declare(strict_types=1);

namespace Libsql\Laravel\Tests\Feature\EloquentAttributeCasting\CastingModels;

use Illuminate\Database\Eloquent\Casts\AsStringable;
use Illuminate\Database\Eloquent\Model;

class StringableCastingModel extends Model
{
    public $timestamps = false;

    protected $guarded = false;

    protected $table = 'stringable_casting_table';

    protected function casts(): array
    {
        return [
            'data' => AsStringable::class,
        ];
    }
}
