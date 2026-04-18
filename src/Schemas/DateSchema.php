<?php
declare(strict_types=1);

namespace Nyra\Zod\Schemas;

use DateTimeInterface;
use Nyra\Zod\Errors\ZodError;
use Nyra\Zod\Errors\ZodIssue;

class DateSchema extends BaseSchema
{
    private ?DateTimeInterface $minDate = null;
    private ?DateTimeInterface $maxDate = null;

    public function parse(mixed $data): DateTimeInterface
    {
        if (!$data instanceof DateTimeInterface) {
            throw new ZodError([
                new ZodIssue('invalid_type', 'Expected instance of DateTimeInterface', []),
            ]);
        }

        $issues = $this->runChecks($data);
        $this->assertNoIssues($issues);
        return $data;
    }

    public function min(DateTimeInterface $min, string $message = 'Date is too small'): self
    {
        $this->minDate = $min;
        $this->checks[] = function (mixed $value, array $path) use ($min, $message): ?ZodIssue {
            if ($value instanceof DateTimeInterface && $value < $min) {
                return new ZodIssue('too_small', $message, $path, ['minimum' => $min->format(DateTimeInterface::ATOM)]);
            }
            return null;
        };
        return $this;
    }

    public function max(DateTimeInterface $max, string $message = 'Date is too big'): self
    {
        $this->maxDate = $max;
        $this->checks[] = function (mixed $value, array $path) use ($max, $message): ?ZodIssue {
            if ($value instanceof DateTimeInterface && $value > $max) {
                return new ZodIssue('too_big', $message, $path, ['maximum' => $max->format(DateTimeInterface::ATOM)]);
            }
            return null;
        };
        return $this;
    }
}
