<?php
declare(strict_types=1);

namespace Nyra\Zod;

use Nyra\Zod\Contracts\Schema;
use Nyra\Zod\Schemas\AnySchema;
use Nyra\Zod\Schemas\ArraySchema;
use Nyra\Zod\Schemas\BaseSchema;
use Nyra\Zod\Schemas\BigIntSchema;
use Nyra\Zod\Schemas\BooleanSchema;
use Nyra\Zod\Schemas\DateSchema;
use Nyra\Zod\Schemas\EnumSchema;
use Nyra\Zod\Schemas\IntersectionSchema;
use Nyra\Zod\Schemas\LazySchema;
use Nyra\Zod\Schemas\LiteralSchema;
use Nyra\Zod\Schemas\NeverSchema;
use Nyra\Zod\Schemas\NullSchema;
use Nyra\Zod\Schemas\NumberSchema;
use Nyra\Zod\Schemas\ObjectSchema;
use Nyra\Zod\Schemas\PreprocessSchema;
use Nyra\Zod\Schemas\RecordSchema;
use Nyra\Zod\Schemas\StringSchema;
use Nyra\Zod\Schemas\TupleSchema;
use Nyra\Zod\Schemas\UnionSchema;
use Nyra\Zod\Schemas\UnknownSchema;
use Nyra\Zod\Serialization\JsonSchemaConverter;

class Z
{
    public static function string(): StringSchema
    {
        return new StringSchema();
    }

    public static function number(): NumberSchema
    {
        return new NumberSchema();
    }

    public static function bigint(): BigIntSchema
    {
        return new BigIntSchema();
    }

    public static function boolean(): BooleanSchema
    {
        return new BooleanSchema();
    }

    public static function date(): DateSchema
    {
        return new DateSchema();
    }

    public static function any(): AnySchema
    {
        return new AnySchema();
    }

    public static function unknown(): UnknownSchema
    {
        return new UnknownSchema();
    }

    public static function never(): NeverSchema
    {
        return new NeverSchema();
    }

    public static function null(): NullSchema
    {
        return new NullSchema();
    }

    public static function literal(mixed $value): LiteralSchema
    {
        return new LiteralSchema($value);
    }

    /**
     * Accepts a list of strings or PHP enum instances. If PHP enums are provided,
     *  their values are extracted for validation.
     *
     *  Example:
     *    Z::enum(['value1', 'value2'])
     *    Z::enum([MyEnum::Case1, MyEnum::Case2])
     *
     * @param list<string|object> $values List of allowed values (string or PHP enum instance)
     */
    public static function enum(array $values): EnumSchema
    {
        $processed = array_map(function ($item) {
            if (is_object($item) && enum_exists(get_class($item))) {
                return $item->value;
            }
            return $item;
        }, $values);

        return new EnumSchema($processed);
    }

    /**
     * @param Schema[] $schemas
     */
    public static function union(array $schemas): UnionSchema
    {
        return new UnionSchema($schemas);
    }

    /**
     * @param list<Schema> $schemas
     */
    public static function tuple(array $schemas): TupleSchema
    {
        return new TupleSchema($schemas);
    }

    public static function intersection(Schema $left, Schema $right): IntersectionSchema
    {
        return new IntersectionSchema($left, $right);
    }

    /**
     * @param Schema $element
     */
    public static function array(Schema $element): ArraySchema
    {
        return new ArraySchema($element);
    }

    /**
     * @param array<string, Schema> $shape
     */
    public static function object(array $shape): ObjectSchema
    {
        return new ObjectSchema($shape);
    }

    public static function record(Schema $value, ?Schema $key = null): RecordSchema
    {
        return new RecordSchema($value, $key);
    }

    /**
     * @param callable():Schema $factory
     */
    public static function lazy(callable $factory): LazySchema
    {
        return new LazySchema($factory);
    }

    /**
     * @param callable(mixed):mixed $preprocess
     */
    public static function preprocess(callable $preprocess, Schema $schema): Schema
    {
        if ($schema instanceof BaseSchema) {
            return $schema->preprocess($preprocess);
        }

        return new PreprocessSchema($preprocess, $schema);
    }

    public static function coerce(): Coerce
    {
        return new Coerce();
    }

    /**
     * Export a schema definition into a JSON-serialisable array.
     *
     * @return array<string, mixed>
     */
    public static function jsonSchema(Schema $schema): array
    {
        return JsonSchemaConverter::convert($schema);
    }
}
