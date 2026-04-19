<?php
// ...existing code...
declare(strict_types=1);

namespace Nyra\Zod\Tests;

use Nyra\Zod\Errors\ZodError;
use Nyra\Zod\Z;
use PHPUnit\Framework\TestCase;

class SetSchemaTest extends TestCase
{
    public function test_set_schema(): void
    {
        $schema = Z::set(Z::string())->min(2);
        $this->assertSame(['a', 'b'], $schema->parse(['a', 'b']));

        // Duplicates should fail
        try {
            $schema->parse(['a', 'a']);
            $this->fail('Should have thrown ZodError for duplicates');
        } catch (ZodError $e) {
            $this->assertSame('invalid_set', $e->getIssues()[0]->code);
        }

        // Min size
        try {
            $schema->parse(['a']);
            $this->fail('Should have thrown ZodError for size');
        } catch (ZodError $e) {
            $this->assertSame('too_small', $e->getIssues()[0]->code);
        }
    }

    public function test_set_max_and_size(): void
    {
        $schema = Z::set(Z::number())->max(2);
        $this->assertSame([1, 2], $schema->parse([1, 2]));
        
        try {
            $schema->parse([1, 2, 3]);
            $this->fail();
        } catch (ZodError) { }

        $schema = Z::set(Z::number())->size(2);
        $this->assertSame([1, 2], $schema->parse([1, 2]));
        
        try {
            $schema->parse([1]);
            $this->fail();
        } catch (ZodError) { }
    }

    public function test_set_invalid_type(): void
    {
        $schema = Z::set(Z::string());
        $this->expectException(ZodError::class);
        $schema->parse('not-an-array');
    }

    public function test_set_element_errors(): void
    {
        $schema = Z::set(Z::number());
        try {
            $schema->parse([1, 'not-a-number']);
            $this->fail();
        } catch (ZodError $e) {
            $this->assertCount(1, $e->getIssues());
            $this->assertEquals(1, $e->getIssues()[0]->path[0]);
        }
    }
}
