<?php
declare(strict_types=1);

namespace Nyra\Zod\Tests;

use DateTime;
use Nyra\Zod\Errors\ZodError;
use Nyra\Zod\Z;
use PHPUnit\Framework\TestCase;

class DateSchemaTest extends TestCase
{
    public function test_parse_date_success(): void
    {
        $d = new DateTime();
        $schema = Z::date();
        $this->assertSame($d, $schema->parse($d));
    }

    public function test_parse_date_invalid_type(): void
    {
        $this->expectException(ZodError::class);
        Z::date()->parse('2023-01-01');
    }

    public function test_min_max_date(): void
    {
        $min = new DateTime('2023-01-01');
        $max = new DateTime('2023-12-31');
        $schema = Z::date()->min($min)->max($max);

        $valid = new DateTime('2023-06-01');
        $this->assertSame($valid, $schema->parse($valid));

        try {
            $schema->parse(new DateTime('2022-12-31'));
            $this->fail('Should have thrown ZodError');
        } catch (ZodError $e) {
            $this->assertCount(1, $e->getIssues());
        }

        try {
            $schema->parse(new DateTime('2024-01-01'));
            $this->fail('Should have thrown ZodError');
        } catch (ZodError $e) {
            $this->assertCount(1, $e->getIssues());
        }
    }
}
