<?php
declare(strict_types=1);

namespace Nyra\Zod\Tests;

use Nyra\Zod\Errors\ZodError;
use Nyra\Zod\Z;
use PHPUnit\Framework\TestCase;

class UnionSchemaTest extends TestCase
{
    public function test_union_success(): void
    {
        $schema = Z::union([
            Z::string(),
            Z::number()->int(),
        ]);

        $this->assertSame('abc', $schema->parse('abc'));
        $this->assertSame(5, $schema->parse(5));
    }

    public function test_union_failure_collects_issues(): void
    {
        $schema = Z::union([
            Z::string(),
            Z::number()->positive(),
        ]);

        try {
            $schema->parse(false);
            $this->fail('Expected ZodError to be thrown');
        } catch (ZodError $error) {
            $issues = $error->getIssues();
            $this->assertSame('invalid_union', $issues[0]->code);
            $this->assertArrayHasKey('errors', $issues[0]->params);
            $this->assertGreaterThanOrEqual(1, count($issues[0]->params['errors']));
        }
    }

    public function test_or(): void
    {
        $schema = Z::string()->or(Z::number());
        $this->assertSame('abc', $schema->parse('abc'));
        $this->assertSame(123, $schema->parse(123));
    }

    public function test_discriminated_union_schema(): void
    {
        $schema = Z::discriminatedUnion('type', [
            Z::object(['type' => Z::literal('a'), 'value' => Z::string()]),
            Z::object(['type' => Z::literal('b'), 'value' => Z::number()]),
        ]);

        $this->assertSame(['type' => 'a', 'value' => 'foo'], $schema->parse(['type' => 'a', 'value' => 'foo']));
        $this->assertSame(['type' => 'b', 'value' => 123], $schema->parse(['type' => 'b', 'value' => 123]));

        // Invalid discriminator
        try {
            $schema->parse(['type' => 'c', 'value' => 'foo']);
            $this->fail('Should have thrown ZodError');
        } catch (ZodError $e) {
            $this->assertSame('invalid_discriminator', $e->getIssues()[0]->code);
        }
    }
}
