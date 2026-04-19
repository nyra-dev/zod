<?php
// ...existing code...
declare(strict_types=1);

namespace Nyra\Zod\Tests;

use Nyra\Zod\Errors\ZodError;
use Nyra\Zod\Z;
use PHPUnit\Framework\TestCase;

class MapSchemaTest extends TestCase
{
    public function test_map_schema(): void
    {
        $schema = Z::map(Z::string(), Z::number());
        $input = ['a' => 1, 'b' => 2];
        $this->assertSame($input, $schema->parse($input));

        // Invalid value
        try {
            $schema->parse(['a' => 'not-a-number']);
            $this->fail('Should have thrown ZodError');
        } catch (ZodError $e) {
            $this->assertSame('invalid_type', $e->getIssues()[0]->code);
            $this->assertSame(['a'], $e->getIssues()[0]->path);
        }
    }

    public function test_map_invalid_type(): void
    {
        $schema = Z::map(Z::string(), Z::number());
        $this->expectException(ZodError::class);
        $schema->parse('not-an-array');
    }

    public function test_map_key_error(): void
    {
        // Use an integer as key which string schema should fail on if strict (but PHP casts int keys in arrays)
        // Let's use a more complex key schema if possible, or just force a mismatch
        $schema = Z::map(Z::string()->min(5), Z::number());
        try {
            $schema->parse(['abc' => 1]);
            $this->fail();
        } catch (ZodError $e) {
            $this->assertSame('too_small', $e->getIssues()[0]->code);
        }
    }
}
