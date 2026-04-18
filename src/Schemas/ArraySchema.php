<?php
declare(strict_types=1);

namespace Nyra\Zod\Schemas;

use Nyra\Zod\Contracts\Schema as SchemaContract;
use Nyra\Zod\Errors\ZodError;
use Nyra\Zod\Errors\ZodIssue;

class ArraySchema extends BaseSchema
{
    private ?int $minItemsConstraint = null;

    private ?int $maxItemsConstraint = null;

    public function __construct(private readonly SchemaContract $element)
    {
    }

    public function parse(mixed $data): array
    {
        if (!is_array($data) || array_is_list($data) === false) {
            throw new ZodError([new ZodIssue('invalid_type', 'Expected array', [])]);
        }

        $issues = $this->runChecks($data);

        $result = [];
        foreach ($data as $idx => $value) {
            try {
                $result[$idx] = $this->element->parse($value);
            } catch (ZodError $e) {
                foreach ($e->getIssues() as $issue) {
                    $issues[] = new ZodIssue($issue->code, $issue->message, array_merge([$idx], $issue->path), $issue->params);
                }
            }
        }

        $this->assertNoIssues($issues);
        return array_values($result);
    }

    public function length(int $length, string $message = 'Array must contain exactly the required number of items'): self
    {
        $this->minItemsConstraint = $length;
        $this->maxItemsConstraint = $length;

        $this->checks[] = function (mixed $value, array $path) use ($length, $message): ?ZodIssue {
            if (is_array($value) && count($value) !== $length) {
                return new ZodIssue('invalid_array_length', $message, $path, ['expected' => $length]);
            }
            return null;
        };
        return $this;
    }

    public function min(int $min, string $message = 'Array is too short'): self
    {
        if ($this->minItemsConstraint === null || $min > $this->minItemsConstraint) {
            $this->minItemsConstraint = $min;
        }

        $this->checks[] = function (mixed $value, array $path) use ($min, $message): ?ZodIssue {
            if (is_array($value) && count($value) < $min) {
                return new ZodIssue('too_small', $message, $path, ['minimum' => $min]);
            }
            return null;
        };
        return $this;
    }

    public function max(int $max, string $message = 'Array is too long'): self
    {
        if ($this->maxItemsConstraint === null || $max < $this->maxItemsConstraint) {
            $this->maxItemsConstraint = $max;
        }

        $this->checks[] = function (mixed $value, array $path) use ($max, $message): ?ZodIssue {
            if (is_array($value) && count($value) > $max) {
                return new ZodIssue('too_big', $message, $path, ['maximum' => $max]);
            }
            return null;
        };
        return $this;
    }

    public function nonempty(string $message = 'Array must contain at least one item'): self
    {
        return $this->min(1, $message);
    }

    public function minItems(int $min, string $message = 'Array is too short'): self
    {
        return $this->min($min, $message);
    }

    public function maxItems(int $max, string $message = 'Array is too long'): self
    {
        return $this->max($max, $message);
    }

    public function element(): SchemaContract
    {
        return $this->element;
    }

    public function getElementSchema(): SchemaContract
    {
        return $this->element;
    }

    public function getMinItemsConstraint(): ?int
    {
        return $this->minItemsConstraint;
    }

    public function getMaxItemsConstraint(): ?int
    {
        return $this->maxItemsConstraint;
    }
}
