<?php
declare(strict_types=1);

namespace Nyra\Zod\Schemas;

use Nyra\Zod\Contracts\Schema as SchemaContract;
use Nyra\Zod\Errors\ZodError;
use Nyra\Zod\Errors\ZodIssue;

class SetSchema extends BaseSchema
{
    private ?int $minSize = null;
    private ?int $maxSize = null;

    public function __construct(private readonly SchemaContract $element)
    {
    }

    public function parse(mixed $data): array
    {
        if (!is_array($data) || !array_is_list($data)) {
            throw new ZodError([new ZodIssue('invalid_type', 'Expected array (set)', [])]);
        }

        $issues = $this->runChecks($data);

        // Ensure uniqueness
        if (count($data) !== count(array_unique($data, SORT_REGULAR))) {
            $issues[] = new ZodIssue('invalid_set', 'Set must contain unique values', []);
        }

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

    public function min(int $size, string $message = 'Set is too small'): self
    {
        $this->minSize = $size;
        $this->checks[] = function (mixed $value, array $path) use ($size, $message): ?ZodIssue {
            if (is_array($value) && count($value) < $size) {
                return new ZodIssue('too_small', $message, $path, ['minimum' => $size]);
            }
            return null;
        };
        return $this;
    }

    public function max(int $size, string $message = 'Set is too big'): self
    {
        $this->maxSize = $size;
        $this->checks[] = function (mixed $value, array $path) use ($size, $message): ?ZodIssue {
            if (is_array($value) && count($value) > $size) {
                return new ZodIssue('too_big', $message, $path, ['maximum' => $size]);
            }
            return null;
        };
        return $this;
    }

    public function size(int $size, string $message = 'Set must contain exactly the required number of items'): self
    {
        return $this->min($size, $message)->max($size, $message);
    }
}
