<?php
declare(strict_types=1);

namespace Nyra\Zod\Tests;

use Nyra\Zod\Errors\ZodError;
use Nyra\Zod\Z;
use PHPUnit\Framework\TestCase;

class RecordSchemaTest extends TestCase
{
    public function test_record_with_value_schema(): void
    {
        $schema = Z::record(Z::number()->int());
        $parsed = $schema->parse(['a' => 1, 'b' => 2]);
        $this->assertSame(['a' => 1, 'b' => 2], $parsed);
    }

    public function test_record_enforces_key_schema(): void
    {
        $schema = Z::record(
            Z::string(),
            Z::enum(['allowed'])
        );

        $this->expectException(ZodError::class);
        $schema->parse(['other' => 'value']);
    }

    public function test_record_invalid_type(): void
    {
        $schema = Z::record(Z::string());
        $this->expectException(ZodError::class);
        $schema->parse('not-an-array');
    }

    public function test_record_value_error(): void
    {
        $schema = Z::record(Z::number());
        try {
            $schema->parse(['a' => 'not-a-number']);
            $this->fail();
        } catch (ZodError $e) {
            $this->assertSame('a', $e->getIssues()[0]->path[0]);
        }
    }

    public function test_record_getters(): void
    {
        $val = Z::number();
        $key = Z::string();
        $schema = Z::record($val, $key);
        
        $this->assertSame($val, $schema->getValueSchema());
        $this->assertSame($key, $schema->getKeySchema());
    }
}

