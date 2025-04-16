<?php

declare(strict_types=1);

namespace Libsql\Laravel\Tests\Feature\EloquentAttributeCasting\Enums;

enum Status: int
{
    case Approved = 1;
    case Pending = 0;
    case Rejected = 2;
}
