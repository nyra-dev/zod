<?php
declare(strict_types=1);

namespace Nyra\Zod\Schemas;

use Nyra\Zod\Contracts\Schema as SchemaContract;

/**
 * @template T of SchemaContract
 */
class BrandedSchema extends BaseSchema
{
    /**
     * @param T $inner
     * @param string $brand
     */
    public function __construct(
        private readonly SchemaContract $inner,
        private readonly string $brand,
    ) {
    }

    public function getInner(): SchemaContract
    {
        return $this->inner;
    }

    public function getBrand(): string
    {
        return $this->brand;
    }

    public function parse(mixed $data): mixed
    {
        return $this->inner->parse($data);
    }
}
