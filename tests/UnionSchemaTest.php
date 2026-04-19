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

        // Missing discriminator key
        try {
            $schema->parse(['value' => 'foo']);
            $this->fail('Should have thrown ZodError');
        } catch (ZodError $e) {
            $this->assertSame('invalid_discriminator', $e->getIssues()[0]->code);
        }

        // Data not array
        try {
            $schema->parse('not an array');
            $this->fail('Should have thrown ZodError');
        } catch (ZodError $e) {
            $this->assertSame('invalid_type', $e->getIssues()[0]->code);
        }
    }

    public function test_discriminated_union_with_enum_discriminator(): void
    {
        $schema = Z::discriminatedUnion('kind', [
            Z::object(['kind' => Z::enum(['x', 'y']), 'value' => Z::string()]),
        ]);
        $this->assertSame(['kind' => 'x', 'value' => 'foo'], $schema->parse(['kind' => 'x', 'value' => 'foo']));
        $this->assertSame(['kind' => 'y', 'value' => 'bar'], $schema->parse(['kind' => 'y', 'value' => 'bar']));
        try {
            $schema->parse(['kind' => 'z', 'value' => 'baz']);
            $this->fail('Should have thrown ZodError');
        } catch (ZodError $e) {
            $this->assertSame('invalid_discriminator', $e->getIssues()[0]->code);
        }
    }

    public function test_discriminated_union_invalid_literal_type(): void
    {
        try {
            Z::discriminatedUnion('type', [
                Z::object(['type' => Z::literal([]), 'value' => Z::string()]),
            ]);
            $this->fail('Should have thrown InvalidArgumentException');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('Discriminator literal must be string or int', $e->getMessage());
        }
    }

    public function test_discriminated_union_invalid_discriminator_schema(): void
    {
        try {
            Z::discriminatedUnion('type', [
                Z::object(['type' => Z::number(), 'value' => Z::string()]),
            ]);
            $this->fail('Should have thrown InvalidArgumentException');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('Discriminator schema must be Z::literal() or Z::enum()', $e->getMessage());
        }
    }

    public function test_discriminated_union_missing_discriminator_in_option(): void
    {
        try {
            Z::discriminatedUnion('type', [
                Z::object(['other' => Z::literal('a')]),
            ]);
            $this->fail();
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('must contain the discriminator key', $e->getMessage());
        }
    }

    public function test_union_empty_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Z::union([]);
    }

    public function test_union_getters(): void
    {
        $options = [Z::string()];
        $schema = Z::union($options);
        $this->assertSame($options, $schema->getOptions());
    }
}
