<?php
declare(strict_types=1);

namespace Nyra\Zod\Serialization;

use Nyra\Zod\Contracts\Schema as SchemaContract;
use Nyra\Zod\Schemas\AnySchema;
use Nyra\Zod\Schemas\ArraySchema;
use Nyra\Zod\Schemas\BaseSchema;
use Nyra\Zod\Schemas\BooleanSchema;
use Nyra\Zod\Schemas\BrandedSchema;
use Nyra\Zod\Schemas\CatchSchema;
use Nyra\Zod\Schemas\DefaultSchema;
use Nyra\Zod\Schemas\EnumSchema;
use Nyra\Zod\Schemas\IntersectionSchema;
use Nyra\Zod\Schemas\LazySchema;
use Nyra\Zod\Schemas\LiteralSchema;
use Nyra\Zod\Schemas\NeverSchema;
use Nyra\Zod\Schemas\NullSchema;
use Nyra\Zod\Schemas\NullableSchema;
use Nyra\Zod\Schemas\NumberSchema;
use Nyra\Zod\Schemas\ObjectSchema;
use Nyra\Zod\Schemas\OptionalSchema;
use Nyra\Zod\Schemas\PreprocessSchema;
use Nyra\Zod\Schemas\RecordSchema;
use Nyra\Zod\Schemas\StringSchema;
use Nyra\Zod\Schemas\TransformSchema;
use Nyra\Zod\Schemas\TupleSchema;
use Nyra\Zod\Schemas\UnionSchema;
use Nyra\Zod\Schemas\UnknownSchema;
use RuntimeException;
use stdClass;

final class JsonSchemaConverter
{
    /**
     * Convert a schema definition into a JSON-schema-compatible associative array.
     *
     * @return array<string, mixed>
     */
    public static function convert(SchemaContract $schema): array
    {
        $description = self::describe($schema);
        $jsonSchema = self::applyNullable($description['schema'], $description['nullable']);

        if ($description['hasDefault']) {
            $jsonSchema['default'] = $description['default'];
        }

        return $jsonSchema;
    }

    /**
     * @return array{schema: array<string, mixed>, nullable: bool, optional: bool, hasDefault: bool, default: mixed, description: ?string}
     */
    private static function describe(SchemaContract $schema): array
    {
        $meta = [
            'nullable' => false,
            'optional' => false,
            'hasDefault' => false,
            'default' => null,
            'description' => null,
        ];

        $core = self::unwrap($schema, $meta);

        if ($core instanceof BaseSchema) {
            if ($core->isOptionalLike()) {
                $meta['optional'] = true;
            }

            if ($core->hasDefault() && $meta['hasDefault'] === false) {
                $meta['hasDefault'] = true;
                $meta['default'] = $core->getDefaultValue();
            }

            if ($meta['description'] === null) {
                $meta['description'] = $core->getDescription();
            }
        }

        $json = self::buildCore($core);

        if ($meta['description'] !== null) {
            $json['description'] = $meta['description'];
        }

        return [
            'schema' => $json,
            'nullable' => $meta['nullable'],
            'optional' => $meta['optional'],
            'hasDefault' => $meta['hasDefault'],
            'default' => $meta['default'],
            'description' => $meta['description'],
        ];
    }

    /**
     * @param array{nullable: bool, optional: bool, hasDefault: bool, default: mixed, description: ?string} $meta
     */
    private static function unwrap(SchemaContract $schema, array &$meta): SchemaContract
    {
        if ($schema instanceof BaseSchema && $meta['description'] === null) {
            $meta['description'] = $schema->getDescription();
        }

        if ($schema instanceof NullableSchema) {
            $meta['nullable'] = true;
            return self::unwrap($schema->getInner(), $meta);
        }

        if ($schema instanceof OptionalSchema) {
            $meta['optional'] = true;
            return self::unwrap($schema->getInner(), $meta);
        }

        if ($schema instanceof DefaultSchema) {
            $meta['optional'] = true;
            $meta['hasDefault'] = true;
            $meta['default'] = $schema->getDefaultValue();
            return self::unwrap($schema->getInner(), $meta);
        }

        if ($schema instanceof BrandedSchema) {
            return self::unwrap($schema->getInner(), $meta);
        }

        if ($schema instanceof CatchSchema) {
            return self::unwrap($schema->getInner(), $meta);
        }

        if ($schema instanceof TransformSchema) {
            return self::unwrap($schema->getInner(), $meta);
        }

        if ($schema instanceof PreprocessSchema) {
            return self::unwrap($schema->getInner(), $meta);
        }

        return $schema;
    }

    /**
     * @return array<string, mixed>
     */
    private static function buildCore(SchemaContract $schema): array
    {
        if ($schema instanceof StringSchema) {
            $json = ['type' => 'string'];
            if (($min = $schema->getMinLength()) !== null) {
                $json['minLength'] = $min;
            }
            if (($max = $schema->getMaxLength()) !== null) {
                $json['maxLength'] = $max;
            }
            if (($pattern = $schema->getPattern()) !== null) {
                $json['pattern'] = $pattern;
            }
            if (($format = $schema->getFormat()) !== null) {
                $json['format'] = $format;
            }
            return $json;
        }

        if ($schema instanceof NumberSchema) {
            $json = ['type' => $schema->isInteger() ? 'integer' : 'number'];
            if (($min = $schema->getMinimum()) !== null) {
                $json['minimum'] = $min;
            }
            if (($max = $schema->getMaximum()) !== null) {
                $json['maximum'] = $max;
            }
            if (($exclusiveMin = $schema->getExclusiveMinimum()) !== null) {
                $json['exclusiveMinimum'] = $exclusiveMin;
            }
            if (($exclusiveMax = $schema->getExclusiveMaximum()) !== null) {
                $json['exclusiveMaximum'] = $exclusiveMax;
            }
            if (($multiple = $schema->getMultipleOf()) !== null) {
                $json['multipleOf'] = $multiple;
            }
            return $json;
        }

        if ($schema instanceof BooleanSchema) {
            return ['type' => 'boolean'];
        }

        if ($schema instanceof NullSchema) {
            return ['type' => 'null'];
        }

        if ($schema instanceof LiteralSchema) {
            $value = $schema->getValue();
            $json = ['enum' => [$value]];
            $type = gettype($value);
            $json['type'] = match ($type) {
                'boolean' => 'boolean',
                'integer' => 'integer',
                'double' => 'number',
                'NULL' => 'null',
                default => 'string',
            };
            return $json;
        }

        if ($schema instanceof EnumSchema) {
            return [
                'type' => 'string',
                'enum' => $schema->values(),
            ];
        }

        if ($schema instanceof ArraySchema) {
            $itemDescription = self::describe($schema->getElementSchema());
            $items = self::applyNullable($itemDescription['schema'], $itemDescription['nullable']);
            if ($itemDescription['hasDefault']) {
                $items['default'] = $itemDescription['default'];
            }

            $json = [
                'type' => 'array',
                'items' => $items,
            ];

            if (($min = $schema->getMinItemsConstraint()) !== null) {
                $json['minItems'] = $min;
            }
            if (($max = $schema->getMaxItemsConstraint()) !== null) {
                $json['maxItems'] = $max;
            }

            return $json;
        }

        if ($schema instanceof TupleSchema) {
            $prefixItems = [];
            foreach ($schema->getItems() as $member) {
                $description = self::describe($member);
                $itemSchema = self::applyNullable($description['schema'], $description['nullable']);
                if ($description['hasDefault']) {
                    $itemSchema['default'] = $description['default'];
                }
                $prefixItems[] = $itemSchema;
            }

            $json = [
                'type' => 'array',
            ];

            if ($prefixItems !== []) {
                $json['prefixItems'] = $prefixItems;
                $json['minItems'] = count($prefixItems);
                if ($schema->getRest() === null) {
                    $json['maxItems'] = count($prefixItems);
                }
            }

            if (($rest = $schema->getRest()) !== null) {
                $restDescription = self::describe($rest);
                $restSchema = self::applyNullable($restDescription['schema'], $restDescription['nullable']);
                if ($restDescription['hasDefault']) {
                    $restSchema['default'] = $restDescription['default'];
                }
                $json['items'] = $restSchema;
            }

            return $json;
        }

        if ($schema instanceof ObjectSchema) {
            $properties = [];
            $required = [];

            foreach ($schema->getShape() as $property => $child) {
                $description = self::describe($child);
                $propertySchema = self::applyNullable($description['schema'], $description['nullable']);
                if ($description['hasDefault']) {
                    $propertySchema['default'] = $description['default'];
                }
                $properties[$property] = $propertySchema;

                if ($description['optional'] === false) {
                    $required[] = $property;
                }
            }

            $json = [
                'type' => 'object',
                'properties' => $properties,
                'additionalProperties' => $schema->getUnknownStrategy() === 'passthrough',
            ];

            if ($required !== []) {
                $json['required'] = array_values($required);
            }

            return $json;
        }

        if ($schema instanceof RecordSchema) {
            $valueDescription = self::describe($schema->getValueSchema());
            $additional = self::applyNullable($valueDescription['schema'], $valueDescription['nullable']);
            if ($valueDescription['hasDefault']) {
                $additional['default'] = $valueDescription['default'];
            }

            $json = [
                'type' => 'object',
                'additionalProperties' => $additional,
            ];

            if (($keySchema = $schema->getKeySchema()) !== null) {
                $keyDescription = self::describe($keySchema);
                $keyJson = self::applyNullable($keyDescription['schema'], $keyDescription['nullable']);
                if ($keyDescription['hasDefault']) {
                    $keyJson['default'] = $keyDescription['default'];
                }
                $json['propertyNames'] = $keyJson;
            }

            return $json;
        }

        if ($schema instanceof UnionSchema) {
            $options = [];
            foreach ($schema->getOptions() as $option) {
                $description = self::describe($option);
                $optionSchema = self::applyNullable($description['schema'], $description['nullable']);
                if ($description['hasDefault']) {
                    $optionSchema['default'] = $description['default'];
                }
                $options[] = $optionSchema;
            }
            return ['anyOf' => $options];
        }

        if ($schema instanceof IntersectionSchema) {
            $left = self::describe($schema->getLeft());
            $right = self::describe($schema->getRight());

            $allOf = [
                self::applyNullable($left['schema'], $left['nullable']),
                self::applyNullable($right['schema'], $right['nullable']),
            ];

            if ($left['hasDefault']) {
                $allOf[0]['default'] = $left['default'];
            }

            if ($right['hasDefault']) {
                $allOf[1]['default'] = $right['default'];
            }

            return ['allOf' => $allOf];
        }

        if ($schema instanceof LazySchema) {
            throw new RuntimeException('Cannot convert lazy schemas to JSON schema.');
        }

        if ($schema instanceof NeverSchema) {
            return ['not' => new stdClass()];
        }

        if ($schema instanceof AnySchema || $schema instanceof UnknownSchema) {
            return [];
        }

        throw new RuntimeException('Unsupported schema type: ' . $schema::class);
    }

    /**
     * @param array<string, mixed> $schema
     * @return array<string, mixed>
     */
    private static function applyNullable(array $schema, bool $nullable): array
    {
        if (!$nullable) {
            return $schema;
        }

        if (isset($schema['enum']) && is_array($schema['enum']) && !in_array(null, $schema['enum'], true)) {
            $schema['enum'][] = null;
        }

        if (isset($schema['anyOf']) && is_array($schema['anyOf'])) {
            $schema['anyOf'][] = ['type' => 'null'];
            return $schema;
        }

        if (isset($schema['oneOf']) && is_array($schema['oneOf'])) {
            $schema['oneOf'][] = ['type' => 'null'];
            return $schema;
        }

        if (isset($schema['type'])) {
            $type = $schema['type'];
            if (is_array($type)) {
                if (!in_array('null', $type, true)) {
                    $type[] = 'null';
                }
                $schema['type'] = $type;
            } elseif ($type !== 'null') {
                $schema['type'] = [$type, 'null'];
            }
            return $schema;
        }

        $schema['type'] = ['null'];
        return $schema;
    }
}
