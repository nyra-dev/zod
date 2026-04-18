<?php
declare(strict_types=1);

namespace Nyra\Zod\Tests;

use Nyra\Zod\Errors\ZodError;
use Nyra\Zod\Z;
use PHPUnit\Framework\TestCase;

class StringSchemaTest extends TestCase
{
    public function test_parse_string_success(): void
    {
        $s = Z::string();
        $this->assertSame('abc', $s->parse('abc'));
    }

    public function test_parse_string_invalid_type(): void
    {
        $this->expectException(ZodError::class);
        Z::string()->parse(123);
    }

    public function test_min_max_and_nonempty(): void
    {
        $s = Z::string()->min(2)->max(5);
        $this->assertSame('ab', $s->parse('ab'));
        $this->assertSame('abcde', $s->parse('abcde'));

        $this->expectException(ZodError::class);
        $s->parse('a');
    }

    public function test_nonempty(): void
    {
        $s = Z::string()->nonempty();
        $this->expectException(ZodError::class);
        $s->parse('');
    }

    public function test_length(): void
    {
        $s = Z::string()->length(3);
        $this->assertSame('abc', $s->parse('abc'));

        $this->expectException(ZodError::class);
        $s->parse('ab');
    }

    public function test_starts_ends_includes(): void
    {
        $s = Z::string()->startsWith('foo')->endsWith('bar')->includes('middle');
        $this->assertSame('foomiddlebar', $s->parse('foomiddlebar'));

        $this->expectException(ZodError::class);
        $s->parse('oomiddlebar');
    }

    public function test_trim_and_case(): void
    {
        $s = Z::string()->trim()->toLowerCase();
        $this->assertSame('abc', $s->parse('  ABC  '));

        $s2 = Z::string()->toUpperCase();
        $this->assertSame('FOO', $s2->parse('foo'));
    }

    public function test_url(): void
    {
        $s = Z::string()->url();
        $this->assertSame('https://google.com', $s->parse('https://google.com'));

        $this->expectException(ZodError::class);
        $s->parse('not-a-url');
    }

    public function test_uuid(): void
    {
        $s = Z::string()->uuid();
        $uuid = '123e4567-e89b-12d3-a456-426614174000';
        $this->assertSame($uuid, $s->parse($uuid));

        $this->expectException(ZodError::class);
        $s->parse('not-a-uuid');
    }
}

