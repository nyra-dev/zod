<?php
declare(strict_types=1);

namespace Nyra\Zod\Tests;

use Nyra\Zod\Errors\ZodError;
use Nyra\Zod\Z;
use PHPUnit\Framework\TestCase;

class ArraySchemaTest extends TestCase
{
    public function test_parse_array_of_strings(): void
    {
        $schema = Z::array(Z::string()->nonempty())->min(2)->max(3);
        $parsed = $schema->parse(['one', 'two']);
        $this->assertSame(['one', 'two'], $parsed);
    }

    public function test_array_length_constraints_fail(): void
    {
        $schema = Z::array(Z::number())->length(2);

        $this->expectException(ZodError::class);
        $schema->parse([1]);
    }

    public function test_nested_array_error_path(): void
    {
        $schema = Z::array(Z::object([
            'name' => Z::string(),
        ]));

        try {
            $schema->parse([['name' => 'valid'], ['name' => 42]]);
            $this->fail('Expected ZodError to be thrown');
        } catch (ZodError $error) {
            $issues = $error->getIssues();
            $this->assertSame([1, 'name'], $issues[0]->path);
        }
    }

    public function test_array_element(): void
    {
        $element = Z::string();
        $arr = Z::array($element);
        $this->assertSame($element, $arr->element());
    }
}
