<?php
declare(strict_types=1);

namespace Nyra\Zod\Tests;

use Nyra\Zod\Errors\ZodError;
use Nyra\Zod\Z;
use PHPUnit\Framework\TestCase;

class ObjectSchemaTest extends TestCase
{
    public function test_object_parsing_with_optional_and_strip(): void
    {
        $schema = Z::object([
            'name' => Z::string()->nonempty(),
            'age' => Z::number()->int()->optional(),
        ]);

        $parsed = $schema->parse(['name' => 'Ada', 'age' => 32, 'unknown' => 'value']);
        $this->assertSame(['name' => 'Ada', 'age' => 32], $parsed);

        $this->assertSame(['name' => 'Grace'], $schema->parse(['name' => 'Grace']));
    }

    public function test_object_passthrough_retains_unknown_keys(): void
    {
        $schema = Z::object([
            'name' => Z::string(),
        ])->passthrough();

        $parsed = $schema->parse(['name' => 'Linus', 'extra' => 1]);
        $this->assertSame(['name' => 'Linus', 'extra' => 1], $parsed);
    }

    public function test_object_strict_rejects_unknown_keys(): void
    {
        $schema = Z::object([
            'name' => Z::string(),
        ])->strict();

        $this->expectException(ZodError::class);
        $schema->parse(['name' => 'Guido', 'extra' => 'nope']);
    }

    public function test_object_missing_required_key(): void
    {
        $schema = Z::object([
            'name' => Z::string(),
            'email' => Z::string(),
        ]);

        $this->expectException(ZodError::class);
        $schema->parse(['name' => 'Margaret']);
    }

    public function test_object_default_values_are_applied(): void
    {
        $schema = Z::object([
            'name' => Z::string(),
            'role' => Z::string()->default('user'),
            'createdAt' => Z::string()->default('pending')->transform('strtoupper'),
        ]);

        $parsed = $schema->parse(['name' => 'Ada']);

        $this->assertSame([
            'name' => 'Ada',
            'role' => 'user',
            'createdAt' => 'PENDING',
        ], $parsed);
    }

    public function test_pick_and_omit(): void
    {
        $base = Z::object([
            'a' => Z::string(),
            'b' => Z::string(),
            'c' => Z::string(),
        ]);

        $picked = $base->pick(['a', 'c']);
        $this->assertSame(['a' => '1', 'c' => '3'], $picked->parse(['a' => '1', 'b' => '2', 'c' => '3']));
        $this->assertArrayNotHasKey('b', $picked->parse(['a' => '1', 'b' => '2', 'c' => '3']));

        $omitted = $base->omit(['b']);
        $this->assertSame(['a' => '1', 'c' => '3'], $omitted->parse(['a' => '1', 'b' => '2', 'c' => '3']));
    }

    public function test_partial_and_required(): void
    {
        $base = Z::object([
            'name' => Z::string(),
            'age' => Z::number(),
        ]);

        $partial = $base->partial();
        $this->assertSame(['name' => 'Ada'], $partial->parse(['name' => 'Ada']));
        $this->assertSame([], $partial->parse([]));

        $required = $partial->required();
        $this->expectException(ZodError::class);
        $required->parse(['name' => 'Ada']); // age is missing
    }

    public function test_merge(): void
    {
        $obj1 = Z::object(['a' => Z::string()]);
        $obj2 = Z::object(['b' => Z::number()]);
        $merged = $obj1->merge($obj2);

        $this->assertSame(['a' => 'foo', 'b' => 123], $merged->parse(['a' => 'foo', 'b' => 123]));
    }

    public function test_object_keyof(): void
    {
        $obj = Z::object(['name' => Z::string(), 'age' => Z::number()]);
        $keySchema = $obj->keyof();
        $this->assertSame('name', $keySchema->parse('name'));
        $this->assertSame('age', $keySchema->parse('age'));
        $this->expectException(ZodError::class);
        $keySchema->parse('unknown');
    }
}

