<?php
declare(strict_types=1);

namespace Nyra\Zod\Schemas;

use Nyra\Zod\Errors\ZodError;
use Nyra\Zod\Errors\ZodIssue;
use InvalidArgumentException;
use ReflectionException;
use UnitEnum;
use BackedEnum;
use ReflectionEnum;

class EnumSchema extends BaseSchema
{
    /** @var list<mixed> */
    private array $values;

    private ?string $enumClass;

    /**
     * @param list<mixed> $values
     * @param string|null $enumClass
     */
    public function __construct(array $values, ?string $enumClass = null)
    {
        if ($values === []) {
            throw new InvalidArgumentException('Enum requires at least one value');
        }

        $this->values = array_values($values);
        $this->enumClass = $enumClass;
    }

    /**
     * @throws ReflectionException
     * @throws ZodError
     */
    public function parse(mixed $data): mixed
    {
        // Hydrate scalar data into an Enum instance if an Enum class is specified
        if (is_scalar($data) && $this->enumClass) {
            if (is_a($this->enumClass, BackedEnum::class, true)) {
                // BackedEnum: native tryFrom is fast and safe
                $hydrated = ($this->enumClass)::tryFrom($data);
                if ($hydrated !== null) {
                    $data = $hydrated;
                }
            } elseif (is_a($this->enumClass, UnitEnum::class, true)) {
                // UnitEnum: use Reflection instead of catching try/catch errors
                $reflection = new ReflectionEnum($this->enumClass);
                $caseName = (string) $data;

                if ($reflection->hasCase($caseName)) {
                    $data = $reflection->getCase($caseName)->getValue();
                }
            }
        }

        // Strict validation against accepted values (matches scalars or Enum singletons)
        if (!in_array($data, $this->values, true)) {
            $expected = implode(', ', array_map(function (mixed $v): string {
                if ($v instanceof UnitEnum) {
                    return get_class($v) . "::{$v->name}";
                }

                // Fallback for standard scalar enums
                return json_encode($v) ?: 'unknown';
            }, $this->values));

            throw new ZodError([
                new ZodIssue('invalid_enum_value', "Expected one of {$expected}", []),
            ]);
        }

        $issues = $this->runChecks($data);
        $this->assertNoIssues($issues);

        return $data;
    }

    /**
     * @return list<mixed>
     */
    public function values(): array
    {
        return $this->values;
    }
}