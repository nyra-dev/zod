<?php
declare(strict_types=1);

namespace Nyra\Zod;

use Nyra\Zod\Schemas\StringSchema;

class Iso
{
    public function datetime(array $options = []): StringSchema
    {
        $message = $options['message'] ?? 'Invalid ISO datetime';
        return Z::string()->refine(function ($value) {
            if (\DateTime::createFromFormat(\DateTimeInterface::ATOM, $value) !== false) return true;
            if (\DateTime::createFromFormat('Y-m-d\TH:i:s.uP', $value) !== false) return true;
            if (\DateTime::createFromFormat('Y-m-d\TH:i:s.u\Z', $value) !== false) return true;
            return false;
        }, $message);
    }

    public function date(string $message = 'Invalid ISO date'): StringSchema
    {
        return Z::string()->refine(function ($value) {
            $d = \DateTime::createFromFormat('Y-m-d', $value);
            return $d && $d->format('Y-m-d') === $value;
        }, $message);
    }

    public function time(string $message = 'Invalid ISO time'): StringSchema
    {
        return Z::string()->refine(function ($value) {
            $d = \DateTime::createFromFormat('H:i:s', $value);
            if ($d && $d->format('H:i:s') === $value) return true;
            $d = \DateTime::createFromFormat('H:i', $value);
            return $d && $d->format('H:i') === $value;
        }, $message);
    }
}
