<?php
declare(strict_types=1);

namespace Nyra\Zod\Schemas;

use Nyra\Zod\Contracts\Schema as SchemaContract;
use Nyra\Zod\Errors\ZodError;
use Nyra\Zod\Errors\ZodIssue;
use Nyra\Zod\Results\ParseResult;
use LogicException;

abstract class BaseSchema implements SchemaContract
{
    /** @var array<callable(mixed, array<int|string>):(?ZodIssue|ZodIssue[])> */
    protected array $checks = [];

    protected ?string $description = null;

    public function safeParse(mixed $data): ParseResult
    {
        try {
            $parsed = $this->parse($data);
            return ParseResult::success($parsed);
        } catch (ZodError $e) {
            return ParseResult::failure($e);
        }
    }

    public function optional(): SchemaContract
    {
        return new OptionalSchema($this);
    }

    public function nullable(): SchemaContract
    {
        return new NullableSchema($this);
    }

    public function default(mixed $value): SchemaContract
    {
        return new DefaultSchema($this, $value);
    }

    public function transform(callable $transform): SchemaContract
    {
        return new TransformSchema($this, $transform);
    }

    public function preprocess(callable $preprocess): SchemaContract
    {
        return new PreprocessSchema($preprocess, $this);
    }

    public function or(SchemaContract $other): SchemaContract
    {
        return new UnionSchema([$this, $other]);
    }

    public function and(SchemaContract $other): SchemaContract
    {
        return new IntersectionSchema($this, $other);
    }

    public function pipe(SchemaContract $other): SchemaContract
    {
        return new PipelineSchema($this, $other);
    }

    public function describe(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function catch(mixed $value): SchemaContract
    {
        return new CatchSchema($this, $value);
    }

    public function brand(string $brand): BrandedSchema
    {
        return new BrandedSchema($this, $brand);
    }

    public function isOptionalLike(): bool
    {
        return false;
    }

    public function hasDefault(): bool
    {
        return false;
    }

    public function getDefaultValue(): mixed
    {
        throw new LogicException('Schema does not define a default value.');
    }

    /**
     * Attach a custom validation that runs after the base parse succeeds.
     *
     * The callable may return true/null for success, false to use the provided message,
     * a string message, or a ZodIssue for full control.
     */
    public function refine(callable $check, string $message = 'Invalid value', string $code = 'custom'): static
    {
        $this->checks[] = function (mixed $value, array $path) use ($check, $message, $code): ?ZodIssue {
            $result = $check($value, $path);
            if ($result === true || $result === null) {
                return null;
            }
            if ($result instanceof ZodIssue) {
                return $result;
            }
            if ($result === false) {
                return new ZodIssue($code, $message, $path);
            }
            if (is_string($result)) {
                return new ZodIssue($code, $result, $path);
            }
            return null;
        };

        return $this;
    }

    /**
     * @param callable(mixed, RefinementContext): void $refinement
     */
    public function superRefine(callable $refinement): static
    {
        $this->checks[] = function (mixed $value, array $path) use ($refinement): array {
            $ctx = new RefinementContext($path);
            $refinement($value, $ctx);
            return $ctx->getIssues();
        };

        return $this;
    }

    /**
     * Helper to run queued checks, returning array of issues (possibly empty).
     *
     * @param mixed $value
     * @param array<int|string> $path
     * @return ZodIssue[]
     */
    protected function runChecks(mixed $value, array $path = []): array
    {
        $issues = [];
        foreach ($this->checks as $check) {
            $issueOrIssues = $check($value, $path);
            if ($issueOrIssues instanceof ZodIssue) {
                $issues[] = $issueOrIssues;
            } elseif (is_array($issueOrIssues)) {
                $issues = array_merge($issues, $issueOrIssues);
            }
        }
        return $issues;
    }

    /**
     * Throw ZodError if issues present.
     *
     * @param ZodIssue[] $issues
     */
    protected function assertNoIssues(array $issues): void
    {
        if (!empty($issues)) {
            throw new ZodError($issues);
        }
    }
}

