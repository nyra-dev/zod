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

    public function test_ip_validation(): void
    {
        $schema = Z::string()->ip();
        $this->assertSame('192.168.1.1', $schema->parse('192.168.1.1'));
        $this->assertSame('2001:0db8:85a3:0000:0000:8a2e:0370:7334', $schema->parse('2001:0db8:85a3:0000:0000:8a2e:0370:7334'));

        $this->expectException(ZodError::class);
        $schema->parse('not-an-ip');
    }

    public function test_ipv4_only(): void
    {
        $schema = Z::string()->ip(['version' => 'v4']);
        $this->assertSame('1.1.1.1', $schema->parse('1.1.1.1'));

        $this->expectException(ZodError::class);
        $schema->parse('2001:0db8:85a3:0000:0000:8a2e:0370:7334');
    }

    public function test_cidr_validation(): void
    {
        $schema = Z::string()->cidr();
        $this->assertSame('192.168.1.0/24', $schema->parse('192.168.1.0/24'));
        $this->assertSame('2001:db8::/32', $schema->parse('2001:db8::/32'));

        try {
            $schema->parse('192.168.1.0/33');
            $this->fail('Should have thrown ZodError');
        } catch (ZodError $e) {
            $this->assertSame('invalid_string', $e->getIssues()[0]->code);
        }
    }

    public function test_base64_validation(): void
    {
        $schema = Z::string()->base64();
        $valid = base64_encode('hello world');
        $this->assertSame($valid, $schema->parse($valid));

        $this->expectException(ZodError::class);
        $schema->parse('!!!not-base64!!!');
    }
}
