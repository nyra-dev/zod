<?php
declare(strict_types=1);

namespace Nyra\Zod\Tests;

use Nyra\Zod\Errors\ZodError;
use Nyra\Zod\Z;
use PHPUnit\Framework\TestCase;

class Phase2CoreSchemaTest extends TestCase
{
    public function test_nan_schema(): void
    {
        $schema = Z::nan();
        $this->assertTrue(is_nan($schema->parse(NAN)));

        $this->expectException(ZodError::class);
        $schema->parse(1.23);
    }

    public function test_void_and_undefined(): void
    {
        $this->assertNull(Z::void()->parse(null));
        $this->assertNull(Z::undefined()->parse(null));

        try {
            Z::void()->parse('foo');
            $this->fail('Should have thrown ZodError');
        } catch (ZodError $e) {
            $this->assertSame('invalid_type', $e->getIssues()[0]->code);
        }
    }

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

    public function test_pipeline_schema(): void
    {
        $schema = Z::string()->trim()->pipe(Z::string()->length(3));
        $this->assertSame('abc', $schema->parse('  abc  '));

        try {
            $schema->parse('  abcd  ');
            $this->fail('Should have thrown ZodError');
        } catch (ZodError $e) {
            $this->assertSame('too_big', $e->getIssues()[0]->code);
        }
    }

    public function test_native_enum(): void
    {
        if (PHP_VERSION_ID < 80100) {
            $this->markTestSkipped('PHP >= 8.1 required for native enums');
        }

        $schema = Z::nativeEnum(\Nyra\Zod\Tests\Fixtures\StatusEnum::class);
        $this->assertSame('active', $schema->parse('active'));
        $this->assertSame('pending', $schema->parse('pending'));

        $this->expectException(ZodError::class);
        $schema->parse('invalid');
    }
}
