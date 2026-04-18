<?php
declare(strict_types=1);

namespace Nyra\Zod\Schemas;

use Nyra\Zod\Errors\ZodError;
use Nyra\Zod\Errors\ZodIssue;

class StringSchema extends BaseSchema
{
    private ?int $minLength = null;

    private ?int $maxLength = null;

    private ?string $pattern = null;

    private ?string $format = null;

    private bool $shouldTrim = false;

    private ?int $case = null; // 1 for lower, 2 for upper

    public function parse(mixed $data): string
    {
        if (!is_string($data)) {
            throw new ZodError([
                new ZodIssue('invalid_type', 'Expected string', []),
            ]);
        }

        if ($this->shouldTrim) {
            $data = trim($data);
        }

        if ($this->case === 1) {
            $data = mb_strtolower($data);
        } elseif ($this->case === 2) {
            $data = mb_strtoupper($data);
        }

        $issues = $this->runChecks($data);
        $this->assertNoIssues($issues);
        return $data;
    }

    public function trim(): self
    {
        $this->shouldTrim = true;
        return $this;
    }

    public function toLowerCase(): self
    {
        $this->case = 1;
        return $this;
    }

    public function lowercase(): self
    {
        return $this->toLowerCase();
    }

    public function toUpperCase(): self
    {
        $this->case = 2;
        return $this;
    }

    public function uppercase(): self
    {
        return $this->toUpperCase();
    }

    public function startsWith(string $prefix, string $message = 'String does not start with required prefix'): self
    {
        $this->checks[] = function (mixed $value, array $path) use ($prefix, $message): ?ZodIssue {
            if (is_string($value) && !str_starts_with($value, $prefix)) {
                return new ZodIssue('invalid_string', $message, $path, ['startsWith' => $prefix]);
            }
            return null;
        };
        return $this;
    }

    public function endsWith(string $suffix, string $message = 'String does not end with required suffix'): self
    {
        $this->checks[] = function (mixed $value, array $path) use ($suffix, $message): ?ZodIssue {
            if (is_string($value) && !str_ends_with($value, $suffix)) {
                return new ZodIssue('invalid_string', $message, $path, ['endsWith' => $suffix]);
            }
            return null;
        };
        return $this;
    }

    public function includes(string $substring, string $message = 'String does not include required substring'): self
    {
        $this->checks[] = function (mixed $value, array $path) use ($substring, $message): ?ZodIssue {
            if (is_string($value) && !str_contains($value, $substring)) {
                return new ZodIssue('invalid_string', $message, $path, ['includes' => $substring]);
            }
            return null;
        };
        return $this;
    }

    public function url(string $message = 'Invalid URL'): self
    {
        $this->format = 'url';
        $this->checks[] = function (mixed $value, array $path) use ($message): ?ZodIssue {
            if (is_string($value) && filter_var($value, FILTER_VALIDATE_URL) === false) {
                return new ZodIssue('invalid_string', $message, $path, ['validation' => 'url']);
            }
            return null;
        };
        return $this;
    }

    public function uuid(string $message = 'Invalid UUID'): self
    {
        $this->format = 'uuid';
        return $this->regex('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $message);
    }

    public function min(int $min, string $message = 'String is too short'): self
    {
        if ($this->minLength === null || $min > $this->minLength) {
            $this->minLength = $min;
        }

        $this->checks[] = function (mixed $value, array $path) use ($min, $message): ?ZodIssue {
            if (is_string($value) && mb_strlen($value) < $min) {
                return new ZodIssue('too_small', $message, $path);
            }
            return null;
        };
        return $this;
    }

    public function max(int $max, string $message = 'String is too long'): self
    {
        if ($this->maxLength === null || $max < $this->maxLength) {
            $this->maxLength = $max;
        }

        $this->checks[] = function (mixed $value, array $path) use ($max, $message): ?ZodIssue {
            if (is_string($value) && mb_strlen($value) > $max) {
                return new ZodIssue('too_big', $message, $path);
            }
            return null;
        };
        return $this;
    }

    public function length(int $length, string $message = 'String must be exactly of specified length'): self
    {
        return $this->min($length, $message)->max($length, $message);
    }

    public function nonempty(string $message = 'String must be nonempty'): self
    {
        return $this->min(1, $message);
    }

    public function regex(string $pattern, string $message = 'Invalid string'): self
    {
        $this->pattern = $pattern;
        $this->checks[] = function (mixed $value, array $path) use ($pattern, $message): ?ZodIssue {
            if (is_string($value) && preg_match($pattern, $value) !== 1) {
                return new ZodIssue('invalid_string', $message, $path);
            }
            return null;
        };
        return $this;
    }

    public function email(string $message = 'Invalid email address'): self
    {
        $this->format = 'email';
        $this->checks[] = function (mixed $value, array $path) use ($message): ?ZodIssue {
            if (is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
                return new ZodIssue('invalid_string', $message, $path);
            }
            return null;
        };
        return $this;
    }

    public function getMinLength(): ?int
    {
        return $this->minLength;
    }

    public function getMaxLength(): ?int
    {
        return $this->maxLength;
    }

    public function getPattern(): ?string
    {
        return $this->pattern;
    }

    public function getFormat(): ?string
    {
        return $this->format;
    }
}
