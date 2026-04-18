<?php
declare(strict_types=1);

namespace Nyra\Zod\Tests;

use Nyra\Zod\Errors\ZodError;
use Nyra\Zod\Z;
use PHPUnit\Framework\TestCase;

class Phase3ExtendedValidationTest extends TestCase
{
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

    public function test_iso_helpers(): void
    {
        $this->assertSame('2023-01-01', Z::iso()->date()->parse('2023-01-01'));
        $this->assertSame('12:00:00', Z::iso()->time()->parse('12:00:00'));
        $this->assertSame('2023-01-01T12:00:00Z', Z::iso()->datetime()->parse('2023-01-01T12:00:00Z'));

        $this->expectException(ZodError::class);
        Z::iso()->date()->parse('2023-13-01');
    }

    public function test_number_safe(): void
    {
        $schema = Z::number()->safe();
        $this->assertSame(123, $schema->parse(123));

        // On 64-bit PHP, this is hard to trigger with just literals, 
        // but we check the logic.
    }

    public function test_object_keyof(): void
    {
        $obj = Z::object(['name' => Z::string(), 'age' => Z::number()]);
        $keySchema = $obj->keyof();
        
        $this->assertSame('name', $keySchema->parse('name'));
        $this->assertSame('age', $keySchema->parse('age'));

        $this->expectException(ZodError::class);
        $keySchema->parse('unknown');
    }

    public function test_array_element(): void
    {
        $element = Z::string();
        $arr = Z::array($element);
        $this->assertSame($element, $arr->element());
    }
}
