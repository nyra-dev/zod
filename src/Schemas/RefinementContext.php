<?php
declare(strict_types=1);

namespace Nyra\Zod\Schemas;

use Nyra\Zod\Errors\ZodIssue;

class RefinementContext
{
    /** @var ZodIssue[] */
    private array $issues = [];

    /**
     * @param array<int|string> $path
     */
    public function __construct(private readonly array $path)
    {
    }

    /**
     * @param array{code?: string, message: string, path?: array<int|string>, params?: array<string, mixed>}|ZodIssue $issue
     */
    public function addIssue(array|ZodIssue $issue): void
    {
        if ($issue instanceof ZodIssue) {
            $this->issues[] = $issue;
            return;
        }

        $this->issues[] = new ZodIssue(
            $issue['code'] ?? 'custom',
            $issue['message'],
            $issue['path'] ?? $this->path,
            $issue['params'] ?? [],
        );
    }

    /**
     * @return ZodIssue[]
     */
    public function getIssues(): array
    {
        return $this->issues;
    }

    /**
     * @return array<int|string>
     */
    public function getPath(): array
    {
        return $this->path;
    }
}
