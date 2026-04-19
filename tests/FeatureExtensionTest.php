<?php
declare(strict_types=1);

namespace Nyra\Zod\Tests;

use PHPUnit\Framework\TestCase;
use Nyra\Zod\Z;
use Nyra\Zod\Errors\ZodError;
use Nyra\Zod\Schemas\RefinementContext;

class FeatureExtensionTest extends TestCase
{
    public function testDescribe(): void
    {
        $schema = Z::string()->describe('A simple string');
        $this->assertEquals('A simple string', $schema->getDescription());
    }

    public function testCatch(): void
    {
        $schema = Z::string()->catch('default');
        $this->assertEquals('hello', $schema->parse('hello'));
        $this->assertEquals('default', $schema->parse(123));

        $schemaWithCallable = Z::string()->catch(fn() => 'dynamic default');
        $this->assertEquals('dynamic default', $schemaWithCallable->parse(123));
    }

    public function testBrand(): void
    {
        $schema = Z::string()->brand('MyBrand');
        $this->assertEquals('MyBrand', $schema->getBrand());
        $this->assertEquals('hello', $schema->parse('hello'));
    }

    public function testSuperRefine(): void
    {
        $schema = Z::number()->superRefine(function (mixed $val, RefinementContext $ctx) {
            if ($val < 10) {
                $ctx->addIssue([
                    'code' => 'too_small',
                    'message' => 'Must be at least 10',
                ]);
            }
            if ($val % 2 !== 0) {
                $ctx->addIssue([
                    'code' => 'invalid_number',
                    'message' => 'Must be even',
                ]);
            }
        });

        $this->assertEquals(12, $schema->parse(12));

        try {
            $schema->parse(7);
            $this->fail('Should have thrown ZodError');
        } catch (ZodError $e) {
            $issues = $e->getIssues();
            $this->assertCount(2, $issues);
            $this->assertEquals('too_small', $issues[0]->code);
            $this->assertEquals('invalid_number', $issues[1]->code);
        }
    }

    public function testDeepPartial(): void
    {
        $schema = Z::object([
            'name' => Z::string(),
            'address' => Z::object([
                'city' => Z::string(),
                'zip' => Z::number(),
            ]),
        ])->deepPartial();

        $input = [
            'address' => [
                'city' => 'New York',
            ],
        ];

        $expected = [
            'address' => [
                'city' => 'New York',
            ],
        ];

        $this->assertEquals($expected, $schema->parse($input));
    }

    public function testCoerceBigInt(): void
    {
        $schema = Z::coerce()->bigint();
        $this->assertEquals(123, $schema->parse('123'));
        $this->assertEquals(1, $schema->parse(true));
        $this->assertEquals(0, $schema->parse(false));
        $this->assertEquals(123, $schema->parse(123.45));
    }

    public function testCoerceDate(): void
    {
        $schema = Z::coerce()->date();
        $date = $schema->parse('2023-01-01');
        $this->assertInstanceOf(\DateTimeInterface::class, $date);
        $this->assertEquals('2023-01-01', $date->format('Y-m-d'));

        $timestamp = time();
        $dateFromTs = $schema->parse($timestamp);
        $this->assertEquals($timestamp, $dateFromTs->getTimestamp());
    }

    public function testJsonSchemaDescription(): void
    {
        $schema = Z::string()->describe('A simple string');
        $json = Z::jsonSchema($schema);
        $this->assertEquals('A simple string', $json['description']);
    }
}
