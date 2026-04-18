<?php
declare(strict_types=1);

namespace Nyra\Zod\Schemas;

use Nyra\Zod\Contracts\Schema as SchemaContract;
use Nyra\Zod\Errors\ZodError;
use Nyra\Zod\Errors\ZodIssue;

class ObjectSchema extends BaseSchema
{
    private const UNKNOWN_STRIP = 'strip';
    private const UNKNOWN_PASSTHROUGH = 'passthrough';
    private const UNKNOWN_STRICT = 'strict';

    /** @var array<string, SchemaContract> */
    private array $shape;

    private string $unknownStrategy = self::UNKNOWN_STRIP;

    /**
     * @param array<string, SchemaContract> $shape
     */
    public function __construct(array $shape)
    {
        $this->shape = $shape;
    }

    public function parse(mixed $data): array
    {
        if (!is_array($data) || ($data !== [] && array_is_list($data))) {
            throw new ZodError([new ZodIssue('invalid_type', 'Expected object (associative array)', [])]);
        }

        $issues = [];
        $result = [];

        foreach ($this->shape as $key => $schema) {
            if (array_key_exists($key, $data)) {
                try {
                    $parsed = $schema->parse($data[$key]);
                    $result[$key] = $parsed;
                } catch (ZodError $e) {
                    foreach ($e->getIssues() as $issue) {
                        $issues[] = new ZodIssue($issue->code, $issue->message, array_merge([$key], $issue->path), $issue->params);
                    }
                }
                continue;
            }

            if ($this->isOptionalShapeMember($schema)) {
                if ($this->hasDefaultFor($schema)) {
                    $result[$key] = $this->getDefaultFor($schema);
                }
                continue;
            }

            $issues[] = new ZodIssue('missing_required', "Missing required key '$key'", [$key]);
        }

        foreach ($data as $key => $value) {
            if (array_key_exists($key, $this->shape)) {
                continue;
            }

            switch ($this->unknownStrategy) {
                case self::UNKNOWN_STRICT:
                    $issues[] = new ZodIssue('unrecognized_key', "Unrecognized key '$key'", [$key]);
                    break;
                case self::UNKNOWN_PASSTHROUGH:
                    $result[$key] = $value;
                    break;
                case self::UNKNOWN_STRIP:
                default:
                    // ignore unknown keys
                    break;
            }
        }

        $issues = array_merge($issues, $this->runChecks($result));
        $this->assertNoIssues($issues);

        return $result;
    }

    public function passthrough(): self
    {
        $this->unknownStrategy = self::UNKNOWN_PASSTHROUGH;
        return $this;
    }

    public function strict(): self
    {
        $this->unknownStrategy = self::UNKNOWN_STRICT;
        return $this;
    }

    public function strip(): self
    {
        $this->unknownStrategy = self::UNKNOWN_STRIP;
        return $this;
    }

    /**
     * @param array<string, SchemaContract> $extension
     */
    public function extend(array $extension): self
    {
        $this->shape = array_merge($this->shape, $extension);
        return $this;
    }

    public function merge(ObjectSchema $other): self
    {
        return $this->extend($other->getShape());
    }

    public function getShape(): array
    {
        return $this->shape;
    }

    public function getUnknownStrategy(): string
    {
        return $this->unknownStrategy;
    }

    /**
     * @param string[] $keys
     */
    public function pick(array $keys): self
    {
        $newShape = [];
        foreach ($keys as $key) {
            if (isset($this->shape[$key])) {
                $newShape[$key] = $this->shape[$key];
            }
        }
        return new self($newShape);
    }

    /**
     * @param string[] $keys
     */
    public function omit(array $keys): self
    {
        $newShape = $this->shape;
        foreach ($keys as $key) {
            unset($newShape[$key]);
        }
        return new self($newShape);
    }

    public function partial(): self
    {
        $newShape = [];
        foreach ($this->shape as $key => $schema) {
            $newShape[$key] = $schema->optional();
        }
        return new self($newShape);
    }

    public function required(): self
    {
        $newShape = [];
        foreach ($this->shape as $key => $schema) {
            if ($schema instanceof OptionalSchema) {
                $newShape[$key] = $schema->getInner();
            } else {
                $newShape[$key] = $schema;
            }
        }
        return new self($newShape);
    }

    private function isOptionalShapeMember(SchemaContract $schema): bool
    {
        return $schema instanceof BaseSchema && $schema->isOptionalLike();
    }

    private function hasDefaultFor(SchemaContract $schema): bool
    {
        return $schema instanceof BaseSchema && $schema->hasDefault();
    }

    private function getDefaultFor(SchemaContract $schema): mixed
    {
        /** @var BaseSchema $schema */
        return $schema->getDefaultValue();
    }
}

