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
}

