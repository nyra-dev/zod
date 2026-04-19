<?php
declare(strict_types=1);

namespace Nyra\Zod;

use Nyra\Zod\Schemas\PreprocessSchema;

class Coerce
{
    public function string(): PreprocessSchema
    {
        return Z::string()->preprocess(static function (mixed $value): mixed {
            if ($value === null) {
                return '';
            }

            if (is_string($value)) {
                return $value;
            }

            if (is_scalar($value)) {
                return (string) $value;
            }

            if (is_object($value) && method_exists($value, '__toString')) {
                return (string) $value;
            }

            return $value;
        });
    }

    public function number(): PreprocessSchema
    {
        return Z::number()->preprocess(static function (mixed $value): mixed {
            if ($value === null) {
                return 0;
            }

            if (is_int($value) || is_float($value)) {
                return $value;
            }

            if (is_bool($value)) {
                return $value ? 1 : 0;
            }

            if (is_string($value)) {
                $trimmed = trim($value);
                if ($trimmed === '') {
                    return 0;
                }

                if (is_numeric($trimmed)) {
                    return strpos($trimmed, '.') === false ? (int) $trimmed : (float) $trimmed;
                }
            }

            return $value;
        });
    }

    public function boolean(): PreprocessSchema
    {
        return Z::boolean()->preprocess(static function (mixed $value): mixed {
            if ($value === null) {
                return false;
            }

            if (is_bool($value)) {
                return $value;
            }

            if (is_int($value) || is_float($value)) {
                return $value == 1;
            }

            if (is_string($value)) {
                $normalized = strtolower(trim($value));
                if (in_array($normalized, ['true', '1', 'yes', 'on'], true)) {
                    return true;
                }
                if (in_array($normalized, ['false', '0', 'no', 'off'], true)) {
                    return false;
                }
            }

            return $value;
        });
    }

    public function bigint(): PreprocessSchema
    {
        return Z::bigint()->preprocess(static function (mixed $value): mixed {
            if ($value === null) {
                return 0;
            }

            if (is_int($value)) {
                return $value;
            }

            if (is_bool($value)) {
                return $value ? 1 : 0;
            }

            if (is_string($value)) {
                $trimmed = trim($value);
                if ($trimmed === '') {
                    return 0;
                }
                if (preg_match('/^-?\d+$/', $trimmed)) {
                    return (int) $trimmed;
                }
            }

            if (is_float($value)) {
                return (int) $value;
            }

            return $value;
        });
    }

    public function date(): PreprocessSchema
    {
        return Z::date()->preprocess(static function (mixed $value): mixed {
            if ($value instanceof \DateTimeInterface) {
                return $value;
            }

            if ($value === null) {
                return $value; // Still return null, as Date has no obvious default coercion
            }

            if (is_int($value)) {
                return (new \DateTimeImmutable())->setTimestamp($value);
            }

            if (is_string($value)) {
                try {
                    return new \DateTimeImmutable($value);
                } catch (\Exception) {
                    return $value;
                }
            }

            return $value;
        });
    }
}

