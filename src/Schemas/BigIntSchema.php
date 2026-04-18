<?php
declare(strict_types=1);

namespace Nyra\Zod\Schemas;

use Nyra\Zod\Errors\ZodError;
use Nyra\Zod\Errors\ZodIssue;

class BigIntSchema extends BaseSchema
{
    public function parse(mixed $data): int
    {
        if (!is_int($data)) {
            throw new ZodError([
                new ZodIssue('invalid_type', 'Expected integer (bigint)', []),
            ]);
        }

        $issues = $this->runChecks($data);
        $this->assertNoIssues($issues);
        return $data;
    }

    public function min(int $min, string $message = 'Number is too small'): self
    {
        $this->checks[] = function (mixed $value, array $path) use ($min, $message): ?ZodIssue {
            if (is_int($value) && $value < $min) {
                return new ZodIssue('too_small', $message, $path, ['minimum' => $min]);
            }
            return null;
        };
        return $this;
    }

    public function max(int $max, string $message = 'Number is too big'): self
    {
        $this->checks[] = function (mixed $value, array $path) use ($max, $message): ?ZodIssue {
            if (is_int($value) && $value > $max) {
                return new ZodIssue('too_big', $message, $path, ['maximum' => $max]);
            }
            return null;
        };
        return $this;
    }

    public function positive(string $message = 'Expected positive number'): self
    {
        return $this->gt(0, $message);
    }

    public function nonnegative(string $message = 'Expected non-negative number'): self
    {
        return $this->min(0, $message);
    }

    public function negative(string $message = 'Expected negative number'): self
    {
        return $this->lt(0, $message);
    }

    public function nonpositive(string $message = 'Expected non-positive number'): self
    {
        return $this->max(0, $message);
    }

    public function gt(int $value, string $message = 'Number must be greater than required value'): self
    {
        $this->checks[] = function (mixed $v, array $path) use ($value, $message): ?ZodIssue {
            if (is_int($v) && $v <= $value) {
                return new ZodIssue('too_small', $message, $path, ['exclusiveMinimum' => $value]);
            }
            return null;
        };
        return $this;
    }

    public function lt(int $value, string $message = 'Number must be less than required value'): self
    {
        $this->checks[] = function (mixed $v, array $path) use ($value, $message): ?ZodIssue {
            if (is_int($v) && $v >= $value) {
                return new ZodIssue('too_big', $message, $path, ['exclusiveMaximum' => $value]);
            }
            return null;
        };
        return $this;
    }
}
