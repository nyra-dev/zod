<?php
declare(strict_types=1);

namespace Nyra\Zod\Tests;

use Nyra\Zod\Errors\ZodError;
use Nyra\Zod\Z;
use PHPUnit\Framework\TestCase;

class BooleanSchemaTest extends TestCase
{
    public function test_accepts_true_and_false(): void
    {
        $schema = Z::boolean();
        $this->assertTrue($schema->parse(true));
        $this->assertFalse($schema->parse(false));
    }

    public function test_rejects_non_boolean_types(): void
    {
        $schema = Z::boolean();
        foreach ([1, 0, 'true', 'false', null, [], new \stdClass()] as $input) {
            try {
                $schema->parse($input);
                $this->fail('Expected ZodError');
            } catch (ZodError $e) {
                $this->assertSame('invalid_type', $e->getIssues()[0]->code);
            }
        }
    }
}
