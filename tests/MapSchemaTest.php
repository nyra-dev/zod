<?php
// ...existing code...
declare(strict_types=1);

namespace Nyra\Zod\Tests;

use Nyra\Zod\Errors\ZodError;
use Nyra\Zod\Z;
use PHPUnit\Framework\TestCase;

class MapSchemaTest extends TestCase
{
    public function test_map_schema(): void
    {
        $schema = Z::map(Z::string(), Z::number());
        $input = ['a' => 1, 'b' => 2];
        $this->assertSame($input, $schema->parse($input));

        // Invalid value
        try {
            $schema->parse(['a' => 'not-a-number']);
            $this->fail('Should have thrown ZodError');
        } catch (ZodError $e) {
            $this->assertSame('invalid_type', $e->getIssues()[0]->code);
            $this->assertSame(['a'], $e->getIssues()[0]->path);
        }
    }
}
