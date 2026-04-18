<?php
// ...existing code...
declare(strict_types=1);

namespace Nyra\Zod\Tests;

use Nyra\Zod\Errors\ZodError;
use Nyra\Zod\Z;
use PHPUnit\Framework\TestCase;

class VoidSchemaTest extends TestCase
{
    public function test_void_and_undefined(): void
    {
        $this->assertNull(Z::void()->parse(null));
        $this->assertNull(Z::undefined()->parse(null));

        try {
            Z::void()->parse('foo');
            $this->fail('Should have thrown ZodError');
        } catch (ZodError $e) {
            $this->assertSame('invalid_type', $e->getIssues()[0]->code);
        }
    }
}
