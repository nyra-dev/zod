<?php
// ...existing code...
declare(strict_types=1);

namespace Nyra\Zod\Tests;

use Nyra\Zod\Errors\ZodError;
use Nyra\Zod\Z;
use PHPUnit\Framework\TestCase;

class NanSchemaTest extends TestCase
{
    public function test_nan_schema(): void
    {
        $schema = Z::nan();
        $this->assertTrue(is_nan($schema->parse(NAN)));

        $this->expectException(ZodError::class);
        $schema->parse(1.23);
    }
}
