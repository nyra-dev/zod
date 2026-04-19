<?php
declare(strict_types=1);

namespace Nyra\Zod\Schemas;

use Nyra\Zod\Contracts\Schema as SchemaContract;
use Nyra\Zod\Errors\ZodError;

class CatchSchema extends BaseSchema
{
    /**
     * @param SchemaContract $inner
     * @param mixed $catchValue
     */
    public function __construct(
        private readonly SchemaContract $inner,
        private readonly mixed $catchValue,
    ) {
    }

    public function getInner(): SchemaContract
    {
        return $this->inner;
    }

    public function parse(mixed $data): mixed
    {
        try {
            return $this->inner->parse($data);
        } catch (ZodError) {
            if (is_callable($this->catchValue)) {
                return ($this->catchValue)();
            }

            return $this->catchValue;
        }
    }
}
