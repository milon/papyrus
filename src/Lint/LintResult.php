<?php

declare(strict_types=1);

namespace Milon\Papyrus\Lint;

final class LintResult
{
    /**
     * @param  list<LintIssue>  $issues
     * @param  list<string>  $fixed
     * @param  list<string>  $skipped
     */
    public function __construct(
        public readonly array $issues = [],
        public readonly array $fixed = [],
        public readonly array $skipped = [],
    ) {}

    public function hasProblems(bool $fixApplied): bool
    {
        if ($this->issues !== [] || $this->skipped !== []) {
            return true;
        }

        return ! $fixApplied && $this->fixed !== [];
    }
}
