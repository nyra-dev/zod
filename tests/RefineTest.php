<?php
declare(strict_types=1);

namespace Nyra\Zod\Tests;

use Nyra\Zod\Errors\ZodError;
use Nyra\Zod\Z;
use PHPUnit\Framework\TestCase;

class RefineTest extends TestCase
{
    public function test_refine_allows_custom_validation(): void
    {
        $schema = Z::string()->refine(fn ($value) => $value === 'ok', 'Must equal ok');
        $this->assertSame('ok', $schema->parse('ok'));

        $this->expectException(ZodError::class);
        $schema->parse('nope');
    }

    public function test_super_refine(): void
    {
        $schema = Z::string()->superRefine(function ($val, \Nyra\Zod\Schemas\RefinementContext $ctx) {
            if ($val !== 'super') {
                $ctx->addIssue(['message' => 'Not super']);
                $ctx->addIssue(new \Nyra\Zod\Errors\ZodIssue('custom', 'Still not super', $ctx->getPath()));
            }
        });

        $this->assertSame('super', $schema->parse('super'));
        
        try {
            $schema->parse('normal');
            $this->fail();
        } catch (ZodError $e) {
            $this->assertCount(2, $e->getIssues());
            $this->assertSame('Not super', $e->getIssues()[0]->message);
            $this->assertSame('Still not super', $e->getIssues()[1]->message);
        }
    }
}

