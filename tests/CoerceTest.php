<?php
declare(strict_types=1);

namespace Nyra\Zod\Tests;

use Nyra\Zod\Z;
use PHPUnit\Framework\TestCase;

class CoerceTest extends TestCase
{
    public function test_number_coercion(): void
    {
        $schema = Z::coerce()->number()->int()->min(3);
        $this->assertSame(5, $schema->parse('5'));
    }

    public function test_boolean_coercion_handles_strings(): void
    {
        $schema = Z::coerce()->boolean();
        $this->assertTrue($schema->parse('yes'));
        $this->assertFalse($schema->parse('0'));
    }

    public function test_string_coercion_casts_scalars(): void
    {
        $schema = Z::coerce()->string()->nonempty();
        $this->assertSame('42', $schema->parse(42));
    }

    public function test_string_coercion_edge_cases(): void
    {
        $schema = Z::coerce()->string();
        $this->assertSame('', $schema->parse(null));
        $this->assertSame('foo', $schema->parse('foo'));
        $this->assertSame('42', $schema->parse(42));
        $this->assertSame('1', $schema->parse(true));
        $this->assertSame('', $schema->parse(false));
        $obj = new class { public function __toString() { return 'bar'; } };
        $this->assertSame('bar', $schema->parse($obj));
        $this->expectException(\Nyra\Zod\Errors\ZodError::class);
        $schema->parse([1,2,3]); // non-scalar, non-string, non-__toString
    }

    public function test_number_coercion_edge_cases(): void
    {
        $schema = Z::coerce()->number();
        $this->assertSame(0, $schema->parse(null));
        $this->assertSame(5, $schema->parse(5));
        $this->assertSame(1, $schema->parse(true));
        $this->assertSame(0, $schema->parse(false));
        $this->assertSame(42, $schema->parse('42'));
        $this->assertSame(3.14, $schema->parse('3.14'));
        $this->expectException(\Nyra\Zod\Errors\ZodError::class);
        $schema->parse('foo');
    }

    public function test_boolean_coercion_edge_cases(): void
    {
        $schema = Z::coerce()->boolean();
        $this->assertTrue($schema->parse(true));
        $this->assertFalse($schema->parse(false));
        $this->assertTrue($schema->parse(1));
        $this->assertFalse($schema->parse(0));
        $this->assertTrue($schema->parse('true'));
        $this->assertTrue($schema->parse('1'));
        $this->assertTrue($schema->parse('yes'));
        $this->assertTrue($schema->parse('on'));
        $this->assertFalse($schema->parse('false'));
        $this->assertFalse($schema->parse('0'));
        $this->assertFalse($schema->parse('no'));
        $this->assertFalse($schema->parse('off'));
        $this->assertFalse($schema->parse(null));
        $this->expectException(\Nyra\Zod\Errors\ZodError::class);
        $schema->parse('foo');
    }

    public function test_bigint_coercion_edge_cases(): void
    {
        $schema = Z::coerce()->bigint();
        $this->assertSame(0, $schema->parse(null));
        $this->assertSame(42, $schema->parse(42));
        $this->assertSame(1, $schema->parse(true));
        $this->assertSame(0, $schema->parse(false));
        $this->assertSame(123, $schema->parse('123'));
        $this->expectException(\Nyra\Zod\Errors\ZodError::class);
        $schema->parse('foo');
    }

    public function test_date_coercion_edge_cases(): void
    {
        $schema = Z::coerce()->date();
        $now = new \DateTimeImmutable();
        $this->assertSame($now, $schema->parse($now));
        $timestamp = 1609459200; // 2021-01-01T00:00:00+00:00
        $date = $schema->parse($timestamp);
        $this->assertInstanceOf(\DateTimeImmutable::class, $date);
        $this->assertSame($timestamp, $date->getTimestamp());
        $valid = $schema->parse('2021-01-01T00:00:00+00:00');
        $this->assertInstanceOf(\DateTimeImmutable::class, $valid);
        $this->assertSame(1609459200, $valid->getTimestamp());
        
        // Test null now throws ZodError because it's not nullable
        try {
            $schema->parse(null);
            $this->fail('Expected ZodError for null input on non-nullable coerced date');
        } catch (\Nyra\Zod\Errors\ZodError $e) {
            $this->assertCount(1, $e->getIssues());
        }

        $this->expectException(\Nyra\Zod\Errors\ZodError::class);
        $schema->parse('foo');
    }
}
