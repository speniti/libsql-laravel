<?php

declare(strict_types=1);

namespace Libsql\Laravel\Tests\Feature\EloquentAttributeCasting\CastingModels;

use Illuminate\Database\Eloquent\Model;

class BooleanCastingModel extends Model
{
    public $timestamps = false;

    protected $guarded = false;

    protected $table = 'boolean_casting_table';

    protected function casts(): array
    {
        return [
            'confirmed' => 'boolean',
        ];
    }
}
