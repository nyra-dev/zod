<?php
declare(strict_types=1);

namespace Nyra\Zod\Tests;

use Nyra\Zod\Errors\ZodError;
use Nyra\Zod\Z;
use PHPUnit\Framework\TestCase;

class NumberSchemaTest extends TestCase
{
    public function test_parse_number_success(): void
    {
        $schema = Z::number()->min(10)->max(20);
        $this->assertSame(12, $schema->parse(12));
    }

    public function test_parse_number_invalid_type(): void
    {
        $this->expectException(ZodError::class);
        Z::number()->parse('foo');
    }

    public function test_int_and_multiple_of(): void
    {
        $schema = Z::number()->int()->multipleOf(5);
        $this->assertSame(10, $schema->parse(10));

        $this->expectException(ZodError::class);
        $schema->parse(12);
    }

    public function test_positive_and_negative_helpers(): void
    {
        $positive = Z::number()->positive();
        $this->assertSame(3, $positive->parse(3));

        $this->expectException(ZodError::class);
        $positive->parse(0);

        $negative = Z::number()->negative();
        $this->assertSame(-2, $negative->parse(-2));
    }

    public function test_gt_lt(): void
    {
        $gt = Z::number()->gt(5);
        $this->assertSame(6, $gt->parse(6));
        
        try {
            $gt->parse(5);
            $this->fail('Should have thrown ZodError');
        } catch (ZodError $e) {
            $this->assertCount(1, $e->getIssues());
        }

        $lt = Z::number()->lt(5);
        $this->assertSame(4, $lt->parse(4));
        
        try {
            $lt->parse(5);
            $this->fail('Should have thrown ZodError');
        } catch (ZodError $e) {
            $this->assertCount(1, $e->getIssues());
        }
    }
}

