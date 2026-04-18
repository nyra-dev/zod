<?php
declare(strict_types=1);

namespace Nyra\Zod\Schemas;

use Nyra\Zod\Contracts\Schema as SchemaContract;
use Nyra\Zod\Errors\ZodError;
use Nyra\Zod\Errors\ZodIssue;

class MapSchema extends BaseSchema
{
    public function __construct(
        private readonly SchemaContract $key,
        private readonly SchemaContract $value
    ) {
    }

    public function parse(mixed $data): array
    {
        if (!is_array($data)) {
            throw new ZodError([new ZodIssue('invalid_type', 'Expected array (map)', [])]);
        }

        $issues = $this->runChecks($data);
        $result = [];

        foreach ($data as $k => $v) {
            try {
                $parsedKey = $this->key->parse($k);
                $parsedValue = $this->value->parse($v);
                $result[$parsedKey] = $parsedValue;
            } catch (ZodError $e) {
                foreach ($e->getIssues() as $issue) {
                    $issues[] = new ZodIssue($issue->code, $issue->message, array_merge([$k], $issue->path), $issue->params);
                }
            }
        }

        $this->assertNoIssues($issues);
        return $result;
    }
}
