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

    public function test_bigint_min_max(): void
    {
        $schema = Z::bigint()->min(10)->max(20);
        $this->assertSame(10, $schema->parse(10));
        $this->assertSame(20, $schema->parse(20));
        
        $this->expectException(ZodError::class);
        $schema->parse(9);
    }

    public function test_bigint_gt_lt(): void
    {
        $schema = Z::bigint()->gt(10)->lt(20);
        $this->assertSame(11, $schema->parse(11));
        $this->assertSame(19, $schema->parse(19));
        
        try {
            $schema->parse(10);
            $this->fail();
        } catch (ZodError) { }
        
        try {
            $schema->parse(20);
            $this->fail();
        } catch (ZodError) { }
    }

    public function test_bigint_negative_nonpositive(): void
    {
        $schema = Z::bigint()->negative();
        $this->assertSame(-1, $schema->parse(-1));
        
        try {
            $schema->parse(0);
            $this->fail();
        } catch (ZodError) { }

        $schema = Z::bigint()->nonpositive();
        $this->assertSame(0, $schema->parse(0));
        $this->assertSame(-1, $schema->parse(-1));
        
        try {
            $schema->parse(1);
            $this->fail();
        } catch (ZodError) { }
    }

    public function test_bigint_nonnegative(): void
    {
        $schema = Z::bigint()->nonnegative();
        $this->assertSame(0, $schema->parse(0));
        $this->assertSame(1, $schema->parse(1));
        
        try {
            $schema->parse(-1);
            $this->fail();
        } catch (ZodError) { }
    }
}
