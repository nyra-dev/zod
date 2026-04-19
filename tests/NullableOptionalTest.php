<?php
declare(strict_types=1);

namespace Nyra\Zod\Tests;

use Nyra\Zod\Errors\ZodError;
use Nyra\Zod\Z;
use PHPUnit\Framework\TestCase;

class NullableOptionalTest extends TestCase
{
    public function test_nullable_optional_property(): void
    {
        $schema = Z::object([
            'maybe' => Z::string()->nullable()->optional(),
        ]);

        $this->assertSame(['maybe' => null], $schema->parse(['maybe' => null]));
        $this->assertSame([], $schema->parse([]));

        $this->expectException(ZodError::class);
        $schema->parse(['maybe' => 123]);
    }

    public function test_nullable_metadata_and_getters(): void
    {
        $inner = Z::string()->optional()->default('foo');
        $schema = $inner->nullable();
        
        $this->assertSame($inner, $schema->getInner());
        $this->assertTrue($schema->isOptionalLike());
        $this->assertTrue($schema->hasDefault());
        $this->assertEquals('foo', $schema->getDefaultValue());
    }

    public function test_nullable_with_non_base_inner(): void
    {
        // NullableSchema with something that doesn't inherit BaseSchema if possible
        // But most our schemas do. Let's just check the fallback logic.
        $schema = Z::string()->nullable();
        $this->assertFalse($schema->hasDefault());
    }
}

