<?php
declare(strict_types=1);

namespace Nyra\Zod\Tests\Fixtures;

enum StatusEnum: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
}
