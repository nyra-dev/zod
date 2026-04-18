<?php
declare(strict_types=1);

namespace Nyra\Zod\Tests;

use Nyra\Zod\Errors\ZodError;
use Nyra\Zod\Z;
use PHPUnit\Framework\TestCase;

class TransformSchemaTest extends TestCase
{
    public function test_transform_applies_callable(): void
    {
        $schema = Z::string()->transform('strtoupper');
        $this->assertSame('HELLO', $schema->parse('hello'));
    }

    public function test_transform_refine_runs_after_transformation(): void
    {
        $schema = Z::string()
            ->transform('strtoupper')
            ->refine(fn (string $value) => $value === 'HELLO', 'Must equal HELLO');

        $this->assertSame('HELLO', $schema->parse('hello'));

        $this->expectException(ZodError::class);
        $schema->parse('nope');
    }

    public function test_transform_respects_default_values(): void
    {
        $schema = Z::object([
            'value' => Z::number()->default(2)->transform(fn (int $number) => $number * 10),
        ]);

        $this->assertSame(['value' => 20], $schema->parse([]));
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
}
