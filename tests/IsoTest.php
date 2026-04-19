<?php
declare(strict_types=1);

namespace Nyra\Zod\Tests;

use Nyra\Zod\Errors\ZodError;
use Nyra\Zod\Z;
use PHPUnit\Framework\TestCase;

class IsoTest extends TestCase
{
    public function test_iso_helpers(): void
    {
        $this->assertSame('2023-01-01', Z::iso()->date()->parse('2023-01-01'));
        $this->assertSame('12:00:00', Z::iso()->time()->parse('12:00:00'));
        $this->assertSame('12:00', Z::iso()->time()->parse('12:00'));
        $this->assertSame('2023-01-01T12:00:00Z', Z::iso()->datetime()->parse('2023-01-01T12:00:00Z'));
        $this->assertSame('2023-01-01T12:00:00.123Z', Z::iso()->datetime()->parse('2023-01-01T12:00:00.123Z'));
        $this->assertSame('2023-01-01T12:00:00+01:00', Z::iso()->datetime()->parse('2023-01-01T12:00:00+01:00'));

        $this->expectException(ZodError::class);
        Z::iso()->date()->parse('2023-13-01');
    }

    public function test_iso_date_invalid(): void
    {
        $schema = Z::iso()->date();
        $this->expectException(ZodError::class);
        $schema->parse('not-a-date');
    }

    public function test_iso_time_invalid(): void
    {
        $schema = Z::iso()->time();
        $this->expectException(ZodError::class);
        $schema->parse('25:00:00');
    }

    public function test_iso_datetime_invalid(): void
    {
        $schema = Z::iso()->datetime();
        $this->expectException(ZodError::class);
        $schema->parse('2023-01-01'); // Missing time part
    }
}
