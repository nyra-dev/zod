<?php
declare(strict_types=1);

namespace Nyra\Zod\Schemas;

use Nyra\Zod\Errors\ZodError;
use Nyra\Zod\Errors\ZodIssue;
use InvalidArgumentException;

class NumberSchema extends BaseSchema
{
    private bool $int = false;

    private ?float $minimum = null;

    private ?float $maximum = null;

    private ?float $exclusiveMinimum = null;

    private ?float $exclusiveMaximum = null;

    private ?float $multipleOfValue = null;

    public function parse(mixed $data): int|float
    {
        if (!is_int($data) && !is_float($data)) {
            throw new ZodError([new ZodIssue('invalid_type', 'Expected number', [])]);
        }

        if ($this->int && !is_int($data)) {
            throw new ZodError([new ZodIssue('invalid_type', 'Expected integer', [])]);
        }

        $issues = $this->runChecks($data);
        $this->assertNoIssues($issues);
        return $data;
    }

    public function min(float $min, string $message = 'Number is too small'): self
    {
        if ($this->minimum === null || $min > $this->minimum) {
            $this->minimum = $min;
        }

        if ($this->exclusiveMinimum !== null && $this->exclusiveMinimum <= $this->minimum) {
            $this->exclusiveMinimum = null;
        }

        $this->checks[] = function (mixed $value, array $path) use ($min, $message): ?ZodIssue {
            if ((is_int($value) || is_float($value)) && $value < $min) {
                return new ZodIssue('too_small', $message, $path, ['minimum' => $min]);
            }
            return null;
        };
        return $this;
    }

    public function gt(float $value, string $message = 'Number must be greater than required value'): self
    {
        if ($this->exclusiveMinimum === null || $value > $this->exclusiveMinimum) {
            $this->exclusiveMinimum = $value;
        }

        if ($this->minimum !== null && $this->minimum <= $this->exclusiveMinimum) {
            $this->minimum = null;
        }

        $this->checks[] = function (mixed $v, array $path) use ($value, $message): ?ZodIssue {
            if ((is_int($v) || is_float($v)) && $v <= $value) {
                return new ZodIssue('too_small', $message, $path, ['exclusiveMinimum' => $value]);
            }
            return null;
        };
        return $this;
    }

    public function max(float $max, string $message = 'Number is too big'): self
    {
        if ($this->maximum === null || $max < $this->maximum) {
            $this->maximum = $max;
        }

        if ($this->exclusiveMaximum !== null && $this->exclusiveMaximum >= $this->maximum) {
            $this->exclusiveMaximum = null;
        }

        $this->checks[] = function (mixed $value, array $path) use ($max, $message): ?ZodIssue {
            if ((is_int($value) || is_float($value)) && $value > $max) {
                return new ZodIssue('too_big', $message, $path, ['maximum' => $max]);
            }
            return null;
        };
        return $this;
    }

    public function lt(float $value, string $message = 'Number must be less than required value'): self
    {
        if ($this->exclusiveMaximum === null || $value < $this->exclusiveMaximum) {
            $this->exclusiveMaximum = $value;
        }

        if ($this->maximum !== null && $this->maximum >= $this->exclusiveMaximum) {
            $this->maximum = null;
        }

        $this->checks[] = function (mixed $v, array $path) use ($value, $message): ?ZodIssue {
            if ((is_int($v) || is_float($v)) && $v >= $value) {
                return new ZodIssue('too_big', $message, $path, ['exclusiveMaximum' => $value]);
            }
            return null;
        };
        return $this;
    }

    public function int(string $message = 'Expected integer'): self
    {
        $this->int = true;
        $this->checks[] = function (mixed $value, array $path) use ($message): ?ZodIssue {
            if (!is_int($value)) {
                return new ZodIssue('invalid_type', $message, $path);
            }
            return null;
        };
        return $this;
    }

    public function positive(string $message = 'Expected positive number'): self
    {
        if ($this->exclusiveMinimum === null || $this->exclusiveMinimum < 0.0) {
            $this->exclusiveMinimum = 0.0;
        }

        $this->checks[] = function (mixed $value, array $path) use ($message): ?ZodIssue {
            if ((is_int($value) || is_float($value)) && $value <= 0) {
                return new ZodIssue('too_small', $message, $path);
            }
            return null;
        };
        return $this;
    }

    public function nonnegative(string $message = 'Expected non-negative number'): self
    {
        if ($this->minimum === null || $this->minimum < 0.0) {
            $this->minimum = 0.0;
        }
        $this->exclusiveMinimum = null;

        $this->checks[] = function (mixed $value, array $path) use ($message): ?ZodIssue {
            if ((is_int($value) || is_float($value)) && $value < 0) {
                return new ZodIssue('too_small', $message, $path);
            }
            return null;
        };
        return $this;
    }

    public function negative(string $message = 'Expected negative number'): self
    {
        if ($this->exclusiveMaximum === null || $this->exclusiveMaximum > 0.0) {
            $this->exclusiveMaximum = 0.0;
        }

        $this->checks[] = function (mixed $value, array $path) use ($message): ?ZodIssue {
            if ((is_int($value) || is_float($value)) && $value >= 0) {
                return new ZodIssue('too_big', $message, $path);
            }
            return null;
        };
        return $this;
    }

    public function nonpositive(string $message = 'Expected non-positive number'): self
    {
        if ($this->maximum === null || $this->maximum > 0.0) {
            $this->maximum = 0.0;
        }
        $this->exclusiveMaximum = null;

        $this->checks[] = function (mixed $value, array $path) use ($message): ?ZodIssue {
            if ((is_int($value) || is_float($value)) && $value > 0) {
                return new ZodIssue('too_big', $message, $path);
            }
            return null;
        };
        return $this;
    }

    public function multipleOf(float $multiple, string $message = 'Number is not a multiple of required value'): self
    {
        if ($multiple == 0.0) {
            throw new InvalidArgumentException('Multiple must be non-zero');
        }

        $this->multipleOfValue = $multiple;

        $this->checks[] = function (mixed $value, array $path) use ($multiple, $message): ?ZodIssue {
            if ((is_int($value) || is_float($value))) {
                $quotient = $value / $multiple;
                if (!is_finite($quotient) || abs($quotient - round($quotient)) > 1e-9) {
                    return new ZodIssue('not_multiple_of', $message, $path, ['multiple' => $multiple]);
                }
            }
            return null;
        };
        return $this;
    }

    public function finite(string $message = 'Expected finite number'): self
    {
        $this->checks[] = function (mixed $value, array $path) use ($message): ?ZodIssue {
            if (is_float($value) && !is_finite($value)) {
                return new ZodIssue('not_finite', $message, $path);
            }
            return null;
        };
        return $this;
    }

    public function isInteger(): bool
    {
        return $this->int;
    }

    public function getMinimum(): ?float
    {
        return $this->minimum;
    }

    public function getMaximum(): ?float
    {
        return $this->maximum;
    }

    public function getExclusiveMinimum(): ?float
    {
        return $this->exclusiveMinimum;
    }

    public function getExclusiveMaximum(): ?float
    {
        return $this->exclusiveMaximum;
    }

    public function getMultipleOf(): ?float
    {
        return $this->multipleOfValue;
    }
}
