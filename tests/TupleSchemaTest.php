<?php
declare(strict_types=1);

namespace Nyra\Zod\Tests;

use Nyra\Zod\Errors\ZodError;
use Nyra\Zod\Z;
use PHPUnit\Framework\TestCase;

class TupleSchemaTest extends TestCase
{
    public function test_tuple_parses_all_items(): void
    {
        $schema = Z::tuple([
            Z::string(),
            Z::number()->int(),
        ]);

        $this->assertSame(['ok', 5], $schema->parse(['ok', 5]));
    }

    public function test_tuple_length_must_match(): void
    {
        $schema = Z::tuple([
            Z::string(),
        ]);

        $this->expectException(ZodError::class);
        $schema->parse(['extra', 'value']);
    }

    public function test_tuple_rest_allows_additional_items(): void
    {
        $schema = Z::tuple([
            Z::number()->int(),
        ])->rest(Z::string());

        $this->assertSame([1, 'a', 'b'], $schema->parse([1, 'a', 'b']));
    }

    public function test_tuple_invalid_type(): void
    {
        $schema = Z::tuple([Z::string()]);
        $this->expectException(ZodError::class);
        $schema->parse('not-an-array');
    }

    public function test_tuple_invalid_length_with_rest(): void
    {
        $schema = Z::tuple([Z::string(), Z::number()])->rest(Z::boolean());
        $this->expectException(ZodError::class);
        $schema->parse(['only-one']);
    }

    public function test_tuple_item_errors(): void
    {
        $schema = Z::tuple([Z::string(), Z::number()]);
        try {
            $schema->parse([1, 'not-a-number']);
            $this->fail();
        } catch (ZodError $e) {
            $this->assertCount(2, $e->getIssues());
        }
    }

    public function test_tuple_rest_errors(): void
    {
        $schema = Z::tuple([Z::string()])->rest(Z::number());
        try {
            $schema->parse(['ok', 'not-a-number']);
            $this->fail();
        } catch (ZodError $e) {
            $this->assertCount(1, $e->getIssues());
            $this->assertEquals(1, $e->getIssues()[0]->path[0]);
        }
    }

    public function test_tuple_getters(): void
    {
        $items = [Z::string()];
        $rest = Z::number();
        $schema = Z::tuple($items)->rest($rest);
        
        $this->assertCount(1, $schema->getItems());
        $this->assertSame($rest, $schema->getRest());
    }
}

