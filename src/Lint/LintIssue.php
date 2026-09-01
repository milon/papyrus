<?php

declare(strict_types=1);

namespace Milon\Papyrus\Lint;

final class LintIssue
{
    public function __construct(
        public readonly string $file,
        public readonly int $line,
        public readonly string $message,
        public readonly string $severity = 'warning',
    ) {}
}
