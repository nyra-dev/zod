<?php
declare(strict_types=1);

namespace Nyra\Zod\Tests;

use Nyra\Zod\Errors\ZodError;
use Nyra\Zod\Z;
use PHPUnit\Framework\TestCase;

class IntersectionSchemaTest extends TestCase
{
    public function test_intersection_merges_object_results(): void
    {
        $schema = Z::intersection(
            Z::object([
                'id' => Z::number()->int(),
            ]),
            Z::object([
                'name' => Z::string(),
            ])
        );

        $parsed = $schema->parse(['id' => 1, 'name' => 'Ada', 'unknown' => true]);

        $this->assertSame([
            'id' => 1,
            'name' => 'Ada',
        ], $parsed);
    }

    public function test_intersection_collects_issues_from_both_sides(): void
    {
        $schema = Z::intersection(
            Z::object([
                'id' => Z::number()->int(),
            ]),
            Z::object([
                'name' => Z::string(),
            ])
        );

        try {
            $schema->parse(['id' => 'oops']);
            $this->fail('Expected ZodError to be thrown');
        } catch (ZodError $error) {
            $issues = $error->getIssues();
            $this->assertCount(2, $issues);
            $this->assertSame('invalid_type', $issues[0]->code);
            $this->assertSame(['id'], $issues[0]->path);
            $this->assertSame('missing_required', $issues[1]->code);
            $this->assertSame(['name'], $issues[1]->path);
        }
    }

    public function test_and(): void
    {
        $schema = Z::object(['a' => Z::string()])->and(Z::object(['b' => Z::number()]));
        $this->assertSame(['a' => 'foo', 'b' => 123], $schema->parse(['a' => 'foo', 'b' => 123]));
    }

    public function test_intersection_metadata_and_getters(): void
    {
        $left = Z::string()->optional()->default('foo');
        $right = Z::string();
        $schema = Z::intersection($left, $right);
        
        $this->assertTrue($schema->isOptionalLike());
        $this->assertTrue($schema->hasDefault());
        $this->assertEquals('foo', $schema->getDefaultValue());
        
        $this->assertSame($left, $schema->getLeft());
        $this->assertSame($right, $schema->getRight());
    }

    public function test_intersection_non_array_merge(): void
    {
        $schema = Z::intersection(Z::string(), Z::string());
        $this->assertSame('foo', $schema->parse('foo'));
        
        $schema2 = Z::intersection(Z::string(), Z::any());
        $this->assertSame('foo', $schema2->parse('foo'));
    }
}

