<?php
declare(strict_types=1);

namespace Nyra\Zod\Schemas;

use Nyra\Zod\Contracts\Schema as SchemaContract;

class OptionalSchema extends BaseSchema
{
    public function __construct(private readonly SchemaContract $inner)
    {
    }

    public function parse(mixed $data): mixed
    {
        if ($data === null) {
            $issues = $this->runChecks($data);
            $this->assertNoIssues($issues);
            return null;
        }

        $value = $this->inner->parse($data);

        $issues = $this->runChecks($value);
        $this->assertNoIssues($issues);

        return $value;
    }

    public function getInner(): SchemaContract
    {
        return $this->inner;
    }

    public function isOptionalLike(): bool
    {
        return true;
    }
}

