<?php
declare(strict_types=1);

namespace Nyra\Zod\Tests;

use Nyra\Zod\Errors\ZodError;
use Nyra\Zod\Z;
use PHPUnit\Framework\TestCase;

class BigIntSchemaTest extends TestCase
{
    public function test_parse_bigint_success(): void
    {
        $schema = Z::bigint();
        $this->assertSame(100, $schema->parse(100));
    }

    public function test_parse_bigint_invalid_type(): void
    {
        $this->expectException(ZodError::class);
        Z::bigint()->parse(1.5);
    }

    public function test_bigint_validations(): void
    {
        $schema = Z::bigint()->positive()->max(100);
        $this->assertSame(50, $schema->parse(50));

        try {
            $schema->parse(0);
            $this->fail('Should have thrown ZodError');
        } catch (ZodError $e) {
            $this->assertCount(1, $e->getIssues());
        }

        try {
            $schema->parse(101);
            $this->fail('Should have thrown ZodError');
        } catch (ZodError $e) {
            $this->assertCount(1, $e->getIssues());
        }
    }
}
