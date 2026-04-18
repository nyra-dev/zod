<?php
declare(strict_types=1);

namespace Nyra\Zod\Tests;

use Nyra\Zod\Errors\ZodError;
use Nyra\Zod\Z;
use PHPUnit\Framework\TestCase;

enum MyStrBackedEnum: string
{
    case Case1 = 'case1';
    case Case2 = 'case2';
    case Case3 = 'case3';
    case Case4 = 'case4';
}

enum MyIntBackedEnum: int
{
    case Case1 = 1;
    case Case2 = 2;
    case Case3 = 3;
    case Case4 = 4;
}

enum MyEnum
{
    case Case1;
    case Case2;
    case Case3;
    case Case4;
}

class EnumSchemaTest extends TestCase
{
    public function test_enum_accepts_known_values(): void
    {
        $schema = Z::enum(['red', 'green', 'blue']);
        $this->assertSame('red', $schema->parse('red'));
    }

    public function test_enum_rejects_unknown_value(): void
    {
        $schema = Z::enum(['red', 'green', 'blue']);

        $this->expectException(ZodError::class);
        $schema->parse('yellow');
    }

    public function test_enum_accepts_php_enum_instances(): void
    {
        $schema = Z::enum([
            MyStrBackedEnum::Case1,
            MyStrBackedEnum::Case2,
            MyStrBackedEnum::Case3,
            MyStrBackedEnum::Case4,
        ]);
        $this->assertSame(MyStrBackedEnum::Case1, $schema->parse(MyStrBackedEnum::Case1));
        $this->assertSame(MyStrBackedEnum::Case1, $schema->parse('case1'));

        $schema = Z::enum([
            MyIntBackedEnum::Case1,
            MyIntBackedEnum::Case2,
            MyIntBackedEnum::Case3,
            MyIntBackedEnum::Case4,
        ]);
        $this->assertSame(MyIntBackedEnum::Case1, $schema->parse(MyIntBackedEnum::Case1));
        $this->assertSame(MyIntBackedEnum::Case1, $schema->parse(1));

        $schema = Z::enum([
            MyEnum::Case1,
            MyEnum::Case2,
            MyEnum::Case3,
            MyEnum::Case4,
        ]);
        $this->assertSame(MyEnum::Case1, $schema->parse(MyEnum::Case1));
        $this->assertSame(MyEnum::Case1, $schema->parse('Case1'));
    }

    public function test_enum_rejects_unknown_php_enum_instance(): void
    {
        $schema = Z::enum([
            MyStrBackedEnum::Case1,
            MyStrBackedEnum::Case2,
        ]);

        $this->expectException(ZodError::class);
        $schema->parse(MyStrBackedEnum::Case3);
    }

    public function test_native_enum(): void
    {
        if (PHP_VERSION_ID < 80100) {
            $this->markTestSkipped('PHP >= 8.1 required for native enums');
        }

        $schema = Z::nativeEnum(\Nyra\Zod\Tests\Fixtures\StatusEnum::class);
        $this->assertSame('active', $schema->parse('active'));
        $this->assertSame('pending', $schema->parse('pending'));

        $this->expectException(ZodError::class);
        $schema->parse('invalid');
    }
}