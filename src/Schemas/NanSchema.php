<?php
declare(strict_types=1);

namespace Nyra\Zod\Schemas;

use Nyra\Zod\Errors\ZodError;
use Nyra\Zod\Errors\ZodIssue;

class NanSchema extends BaseSchema
{
    public function parse(mixed $data): float
    {
        if (!is_float($data) || !is_nan($data)) {
            throw new ZodError([
                new ZodIssue('invalid_type', 'Expected NaN', []),
            ]);
        }

        $issues = $this->runChecks($data);
        $this->assertNoIssues($issues);
        return $data;
    }
}
