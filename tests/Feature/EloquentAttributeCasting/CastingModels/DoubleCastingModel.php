<?php

declare(strict_types=1);

namespace Libsql\Laravel\Tests\Feature\EloquentAttributeCasting\CastingModels;

use Illuminate\Database\Eloquent\Model;

class DoubleCastingModel extends Model
{
    public $timestamps = false;

    protected $guarded = false;

    protected $table = 'double_casting_table';

    protected function casts(): array
    {
        return [
            'amount' => 'double',
        ];
    }
}
