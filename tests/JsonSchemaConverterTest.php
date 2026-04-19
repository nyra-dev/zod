<?php
declare(strict_types=1);

namespace Nyra\Zod\Tests;

use Nyra\Zod\Z;
use PHPUnit\Framework\TestCase;

class JsonSchemaConverterTest extends TestCase
{
    public function test_openai_schema_example(): void
    {
        $schema = Z::object([
            'name' => Z::string(),
            'summary' => Z::string()->max(100),
            'description' => Z::string(),
        ]);

        $json = Z::jsonSchema($schema);

        $this->assertSame([
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string'],
                'summary' => ['type' => 'string', 'maxLength' => 100],
                'description' => ['type' => 'string'],
            ],
            'additionalProperties' => false,
            'required' => ['name', 'summary', 'description'],
        ], $json);
    }

    public function test_optional_nullable_and_defaults_are_reflected(): void
    {
        $schema = Z::object([
            'id' => Z::number()->int()->positive(),
            'title' => Z::string()->optional()->nullable(),
            'status' => Z::enum(['draft', 'published'])->default('draft'),
            'tags' => Z::array(Z::string()->nonempty())->nonempty()->optional(),
        ]);

        $json = Z::jsonSchema($schema);

        $this->assertSame([
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'exclusiveMinimum' => 0.0,
                ],
                'title' => [
                    'type' => ['string', 'null'],
                ],
                'status' => [
                    'type' => 'string',
                    'enum' => ['draft', 'published'],
                    'default' => 'draft',
                ],
                'tags' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                        'minLength' => 1,
                    ],
                    'minItems' => 1,
                ],
            ],
            'additionalProperties' => false,
            'required' => ['id'],
        ], $json);
    }

    public function test_open_ai_schema()
    {
        $schema = Z::object([
            'name' => Z::string(),
            'summary' => Z::string()->max(100),
            'description' => Z::string(),
        ])->strict();

        $json = Z::jsonSchema($schema);

        $this->assertSame([
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string'],
                'summary' => ['type' => 'string', 'maxLength' => 100],
                'description' => ['type' => 'string'],
            ],
            'additionalProperties' => false,
            'required' => ['name', 'summary', 'description'],
        ], $json);
    }

    public function test_boolean_and_null_schema(): void
    {
        $this->assertSame(['type' => 'boolean'], Z::jsonSchema(Z::boolean()));
        $this->assertSame(['type' => 'null'], Z::jsonSchema(Z::null()));
    }

    public function test_literal_schema(): void
    {
        $this->assertSame(['enum' => [42], 'type' => 'integer'], Z::jsonSchema(Z::literal(42)));
        $this->assertSame(['enum' => ['foo'], 'type' => 'string'], Z::jsonSchema(Z::literal('foo')));
        $this->assertSame(['enum' => [true], 'type' => 'boolean'], Z::jsonSchema(Z::literal(true)));
    }

    public function test_number_schema(): void
    {
        $schema = Z::number()->min(1)->max(10)->int();
        $json = Z::jsonSchema($schema);
        $this->assertSame(['type' => 'integer', 'minimum' => 1.0, 'maximum' => 10.0], $json);
    }

    public function test_union_schema(): void
    {
        $schema = Z::union([Z::string(), Z::number()]);
        $json = Z::jsonSchema($schema);
        $this->assertSame([
            'anyOf' => [
                ['type' => 'string'],
                ['type' => 'number'],
            ],
        ], $json);
    }

    public function test_intersection_schema(): void
    {
        $schema = Z::intersection(Z::object(['a' => Z::string()]), Z::object(['b' => Z::number()]));
        $json = Z::jsonSchema($schema);
        $this->assertSame([
            'allOf' => [
                [
                    'type' => 'object',
                    'properties' => ['a' => ['type' => 'string']],
                    'additionalProperties' => false,
                    'required' => ['a'],
                ],
                [
                    'type' => 'object',
                    'properties' => ['b' => ['type' => 'number']],
                    'additionalProperties' => false,
                    'required' => ['b'],
                ],
            ],
        ], $json);
    }

    public function test_record_schema(): void
    {
        $schema = Z::record(Z::number(), Z::string());
        $json = Z::jsonSchema($schema);
        $this->assertSame([
            'type' => 'object',
            'additionalProperties' => ['type' => 'number'],
            'propertyNames' => ['type' => 'string'],
        ], $json);
    }

    public function test_nullable_optional_default(): void
    {
        $schema = Z::string()->nullable()->optional()->default('foo');
        $json = Z::jsonSchema($schema);
        $this->assertSame([
            'type' => ['string', 'null'],
            'default' => 'foo',
        ], $json);
    }

    public function test_strict_and_passthrough_object(): void
    {
        $strict = Z::object(['a' => Z::string()])->strict();
        $passthrough = Z::object(['a' => Z::string()])->passthrough();
        $this->assertFalse(Z::jsonSchema($strict)['additionalProperties']);
        $this->assertTrue(Z::jsonSchema($passthrough)['additionalProperties']);
    }

    public function test_tuple_schema(): void
    {
        $schema = Z::tuple([Z::string(), Z::number()])->rest(Z::boolean());
        $json = Z::jsonSchema($schema);
        $this->assertSame([
            'type' => 'array',
            'prefixItems' => [
                ['type' => 'string'],
                ['type' => 'number'],
            ],
            'minItems' => 2,
            'items' => ['type' => 'boolean'],
        ], $json);
    }

    public function test_never_schema(): void
    {
        $this->assertEquals(['not' => new \stdClass()], Z::jsonSchema(Z::never()));
    }

    public function test_any_unknown_schema(): void
    {
        $this->assertSame([], Z::jsonSchema(Z::any()));
        $this->assertSame([], Z::jsonSchema(Z::unknown()));
    }

    public function test_lazy_schema_throws(): void
    {
        $this->expectException(\RuntimeException::class);
        Z::jsonSchema(Z::lazy(fn() => Z::string()));
    }

    public function test_literal_edge_cases(): void
    {
        $this->assertSame(['enum' => [3.14], 'type' => 'number'], Z::jsonSchema(Z::literal(3.14)));
        $this->assertSame(['enum' => [null], 'type' => 'null'], Z::jsonSchema(Z::literal(null)));
    }

    public function test_description_is_preserved(): void
    {
        $schema = Z::string()->describe('A simple string');
        $json = Z::jsonSchema($schema);
        $this->assertSame('A simple string', $json['description']);
    }

    public function test_wrappers_unwrapping(): void
    {
        $schema = Z::string()
            ->brand('foo')
            ->catch('bar')
            ->transform(fn($v) => $v)
            ->preprocess(fn($v) => $v);
        
        $json = Z::jsonSchema($schema);
        $this->assertSame('string', $json['type']);
    }

    public function test_union_with_nullable(): void
    {
        $schema = Z::union([Z::string(), Z::number()])->nullable();
        $json = Z::jsonSchema($schema);
        $this->assertCount(3, $json['anyOf']);
        $this->assertSame(['type' => 'null'], $json['anyOf'][2]);
    }
}
