<?php
declare(strict_types=1);

namespace Nyra\Zod\Schemas;

use Nyra\Zod\Contracts\Schema as SchemaContract;

class PipelineSchema extends BaseSchema
{
    public function __construct(
        private readonly SchemaContract $left,
        private readonly SchemaContract $right
    ) {
    }

    public function parse(mixed $data): mixed
    {
        $value = $this->left->parse($data);
        return $this->right->parse($value);
    }
}
