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
}
