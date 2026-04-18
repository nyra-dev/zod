<?php
declare(strict_types=1);

namespace Nyra\Zod\Schemas;

use Nyra\Zod\Contracts\Schema as SchemaContract;
use Nyra\Zod\Errors\ZodError;
use Nyra\Zod\Errors\ZodIssue;
use InvalidArgumentException;

class DiscriminatedUnionSchema extends BaseSchema
{
    /** @var array<string, SchemaContract> */
    private array $optionsMap = [];

    /**
     * @param string $discriminator The key used for discrimination
     * @param ObjectSchema[] $options
     */
    public function __construct(
        private readonly string $discriminator,
        array $options
    ) {
        foreach ($options as $option) {
            $shape = $option->getShape();
            if (!isset($shape[$this->discriminator])) {
                throw new InvalidArgumentException("Each option in discriminatedUnion must contain the discriminator key: '{$this->discriminator}'");
            }

            $discriminatorSchema = $shape[$this->discriminator];
            if ($discriminatorSchema instanceof LiteralSchema) {
                $value = $discriminatorSchema->getValue();
                if (!is_string($value) && !is_int($value)) {
                     throw new InvalidArgumentException("Discriminator literal must be string or int");
                }
                $this->optionsMap[(string)$value] = $option;
            } elseif ($discriminatorSchema instanceof EnumSchema) {
                foreach ($discriminatorSchema->values() as $value) {
                    $this->optionsMap[(string)$value] = $option;
                }
            } else {
                throw new InvalidArgumentException("Discriminator schema must be Z::literal() or Z::enum()");
            }
        }
    }

    public function parse(mixed $data): array
    {
        if (!is_array($data)) {
            throw new ZodError([new ZodIssue('invalid_type', 'Expected object', [])]);
        }

        if (!isset($data[$this->discriminator])) {
            throw new ZodError([
                new ZodIssue('invalid_discriminator', "Missing discriminator key: '{$this->discriminator}'", [$this->discriminator])
            ]);
        }

        $discriminatorValue = (string)$data[$this->discriminator];

        if (!isset($this->optionsMap[$discriminatorValue])) {
            $allowed = implode("', '", array_keys($this->optionsMap));
            throw new ZodError([
                new ZodIssue('invalid_discriminator', "Invalid discriminator value. Expected one of: '{$allowed}'", [$this->discriminator])
            ]);
        }

        return $this->optionsMap[$discriminatorValue]->parse($data);
    }
}
